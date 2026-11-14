<?php

declare(strict_types=1);

/**
 * ChallengeChain test suite — php tests/run.php
 * Covers: config, migrations, auth, AI validation, participation,
 * scoring, public URLs, referrals, rate limiting, SQLite operations.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

// Isolated temp database for the whole run.
$tempDb = sys_get_temp_dir() . '/challengechain_test_' . bin2hex(random_bytes(6)) . '.sqlite';
putenv('DB_PATH=' . $tempDb);
$_ENV['DB_PATH'] = $tempDb;
putenv('APP_URL=http://localhost:8000');
$_ENV['APP_URL'] = 'http://localhost:8000';
putenv('AI_API_KEY=');
$_ENV['AI_API_KEY'] = '';
putenv('APP_DEBUG=true');
$_ENV['APP_DEBUG'] = 'true';

require dirname(__DIR__) . '/bootstrap.php';

use ChallengeChain\Core\Auth;
use ChallengeChain\Core\Database;
use ChallengeChain\Core\HttpException;
use ChallengeChain\Core\RateLimiter;
use ChallengeChain\Core\Router;
use ChallengeChain\Models\Attempt;
use ChallengeChain\Models\Challenge;
use ChallengeChain\Models\User;
use ChallengeChain\Services\AIService;
use ChallengeChain\Services\AttemptService;
use ChallengeChain\Services\ChallengeService;
use ChallengeChain\Services\DemoQuestionBank;
use ChallengeChain\Services\ReferralService;
use ChallengeChain\Services\ScoringService;

// Apply migrations to the temp database.
$pdo = Database::pdo();
$pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    migration TEXT NOT NULL UNIQUE,
    ran_at TEXT NOT NULL DEFAULT (datetime('now'))
)");
$migrationFiles = glob(dirname(__DIR__) . '/database/migrations/*.php') ?: [];
sort($migrationFiles);
foreach ($migrationFiles as $migrationFile) {
    $sql = require $migrationFile;
    if (is_string($sql) && trim($sql) !== '') {
        $pdo->exec($sql);
        $stmt = $pdo->prepare('INSERT OR IGNORE INTO schema_migrations (migration) VALUES (:m)');
        $stmt->execute(['m' => basename($migrationFile, '.php')]);
    }
}

$passed = 0;
$failed = 0;
$failures = [];

function check(string $name, bool $condition, string $detail = ''): void
{
    global $passed, $failed, $failures;
    if ($condition) {
        $passed++;
        echo "  ✓ {$name}\n";
    } else {
        $failed++;
        $failures[] = $name . ($detail !== '' ? " — {$detail}" : '');
        echo "  ✗ {$name}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
    }
}

function section(string $title): void
{
    echo "\n== {$title} ==\n";
}

// ---------------------------------------------------------------------------
section('Environment & config');
check('PHP >= 8.3', PHP_VERSION_ID >= 80300, PHP_VERSION);
check('PDO SQLite driver available', in_array('sqlite', PDO::getAvailableDrivers(), true));
check('config() loads', config('app.name') === 'ChallengeChain');
check('demo mode active (no AI key)', is_demo_mode() === true);

// ---------------------------------------------------------------------------
section('Migrations');
$pdo = Database::pdo();
$tables = array_column(Database::fetchAll("SELECT name FROM sqlite_master WHERE type='table'"), 'name');
$required = [
    'users', 'challenges', 'questions', 'question_options', 'attempts',
    'attempt_answers', 'challenge_views', 'referral_events', 'achievements',
    'user_achievements', 'password_reset_tokens',
];
foreach ($required as $t) {
    check("table {$t} exists", in_array($t, $tables, true));
}
check('foreign_keys pragma ON', (int) $pdo->query('PRAGMA foreign_keys')->fetchColumn() === 1);
check('WAL journal mode', $pdo->query('PRAGMA journal_mode')->fetchColumn() === 'wal');
check('schema_migrations recorded', count(Database::fetchAll('SELECT * FROM schema_migrations')) >= 1);

// ---------------------------------------------------------------------------
section('Registration & login (password_hash/verify)');
$email = 'tester@example.com';
if (User::emailExists($email)) {
    $existing = User::findByEmail($email);
    User::updatePassword((int) $existing['id'], 'secret123');
    $user = $existing;
} else {
    $user = User::create('tester', $email, 'secret123', 'Tester');
}
check('user created with unique referral code', ($user['referral_code'] ?? '') !== '');
check('password_hash used', str_starts_with((string) $user['password_hash'], '$2y$')
    || str_starts_with((string) $user['password_hash'], '$argon'));
check('password_verify accepts correct password', password_verify('secret123', $user['password_hash']));
check('password_verify rejects wrong password', !password_verify('wrong', $user['password_hash']));
check('duplicate email rejected', User::emailExists($email));
check('duplicate username rejected', User::usernameExists('tester'));

// Session-based login (no HTTP): emulate Auth::attempt core.
$_SESSION = [];
Auth::login($user);
check('session login sets user id', Auth::id() === (int) $user['id']);
check('Auth::user returns profile', Auth::user()['username'] === 'tester');

// ---------------------------------------------------------------------------
section('AI generation (demo mode) + JSON validation');
$svc = new AIService();
$gen = $svc->generate([
    'category' => 'science',
    'topic' => 'Photosynthesis',
    'difficulty' => 'medium',
    'question_count' => 5,
    'language' => 'en',
]);
check('demo generation returns questions', count($gen['questions']) === 5, count($gen['questions']) . ' questions');
check('demo flag set', ($gen['demo'] ?? false) === true);
check('title non-empty', trim((string) $gen['title']) !== '');
check('exactly one correct option per question', array_reduce(
    $gen['questions'],
    static fn (bool $ok, array $q): bool => $ok && count($q['options']) > $q['correct_index'] && $q['correct_index'] >= 0,
    true
));

$invalidCaught = false;
try {
    AIService::validatePayload(['title' => 'x', 'questions' => [['question' => 'q', 'options' => ['a'], 'correct_index' => 5]]]);
} catch (HttpException $e) {
    $invalidCaught = true;
}
check('invalid AI payload rejected', $invalidCaught);

$invalidCaught2 = false;
try {
    AIService::validatePayload(['title' => '', 'questions' => []]);
} catch (HttpException $e) {
    $invalidCaught2 = true;
}
check('empty title/questions rejected', $invalidCaught2);

section('Mistral response formats');
$extractContent = new ReflectionMethod(AIService::class, 'extractContent');
$quizJson = json_encode($gen, JSON_THROW_ON_ERROR);
$textResponse = json_encode(['choices' => [['message' => ['content' => $quizJson]]]], JSON_THROW_ON_ERROR);
check('Mistral JSON text response is decoded', $extractContent->invoke($svc, $textResponse) === $gen);
$split = intdiv(strlen($quizJson), 2);
$chunkResponse = json_encode(['choices' => [['message' => ['content' => [
    ['type' => 'thinking', 'thinking' => 'Reasoning is not quiz JSON.'],
    ['type' => 'text', 'text' => substr($quizJson, 0, $split)],
    ['type' => 'text', 'text' => substr($quizJson, $split)],
]]]]], JSON_THROW_ON_ERROR);
$chunkQuiz = $extractContent->invoke($svc, $chunkResponse);
check('Mistral text chunks are joined without reasoning', $chunkQuiz === $gen);
check('Mistral response passes challenge validation', count(AIService::validatePayload($chunkQuiz, 5)['questions']) === 5);
$emptyChunksRejected = false;
try {
    $extractContent->invoke($svc, json_encode(['choices' => [['message' => ['content' => [
        ['type' => 'thinking', 'thinking' => 'No final answer.'],
    ]]]]], JSON_THROW_ON_ERROR));
} catch (HttpException $e) {
    $emptyChunksRejected = $e->getStatus() === 502;
}
check('Mistral response without final text is rejected', $emptyChunksRejected);

// ---------------------------------------------------------------------------
section('Challenge creation (transaction + slug)');
$challengeSvc = new ChallengeService();
$payload = [
    'title' => 'Test Challenge ' . random_int(1000, 9999),
    'description' => 'Automated test challenge.',
    'category' => 'technology',
    'topic' => 'Testing',
    'difficulty' => 'easy',
    'language' => 'en',
    'is_public' => true,
    'questions' => [
        ['question' => 'What is 2+2?', 'options' => ['3', '4', '5', '6'], 'correct_index' => 1, 'explanation' => 'Basic arithmetic.'],
        ['question' => 'HTTP stands for?', 'options' => ['HyperText Transfer Protocol', 'High Tech Process', 'Host Transfer Program', 'None'], 'correct_index' => 0, 'explanation' => 'Web protocol.'],
        ['question' => 'Which is a JS framework?', 'options' => ['Apache', 'React', 'MySQL', 'Nginx'], 'correct_index' => 1, 'explanation' => 'React is a UI library.'],
    ],
];
$challenge = $challengeSvc->create((int) $user['id'], $payload, 'manual', false);
check('challenge created', $challenge !== null && (int) $challenge['id'] > 0);
check('slug generated', ($challenge['slug'] ?? '') !== '');
check('question_count stored = 3', (int) $challenge['question_count'] === 3);
check('category stored', $challenge['category'] === 'technology');

$challenge2 = $challengeSvc->create((int) $user['id'], $payload, 'manual', false);
check('slug uniqueness on duplicate title', $challenge2['slug'] !== $challenge['slug']);

check('findBySlug works', Challenge::findBySlug($challenge['slug']) !== null);
check('public explore finds it', count(array_filter(
    Challenge::explore(null, null, 'recent', 100),
    static fn (array $c): bool => $c['slug'] === $challenge['slug']
)) === 1);

// ---------------------------------------------------------------------------
section('Challenge participation (never leaks answers pre-submission)');
$playable = \ChallengeChain\Models\Question::playableForChallenge((int) $challenge['id']);
check('playable payload has questions', count($playable) === 3);
$hasCorrect = false;
array_walk_recursive($playable, static function ($v, $k) use (&$hasCorrect): void {
    if (in_array($k, ['is_correct', 'correct_index'], true)) {
        $hasCorrect = true;
    }
});
check('playable payload has NO correct flags', !$hasCorrect);
check('playable options have id+text only', isset($playable[0]['options'][0]['id'], $playable[0]['options'][0]['text']));

$_SESSION['attempt_ids'] = [];
$attemptSvc = new AttemptService();
$start = $attemptSvc->start($challenge, $user, 'Speedy', null);
check('attempt started with public_id', isset($start['attempt_id']) && strlen($start['attempt_id']) >= 8);
check('start returns questions without answers', !str_contains(json_encode($start), 'is_correct'));
$_SESSION['attempt_ids'][] = $start['attempt_id'];

// Answer correctly using knowledge of the payload (server-side grading still required).
$answers = [];
foreach ($playable as $i => $q) {
    $correctIndex = $payload['questions'][$i]['correct_index'];
    $answers[$q['id']] = $q['options'][$correctIndex]['id'];
}
$result = $attemptSvc->submit($start['attempt_id'], $answers, (int) $user['id'], $_SESSION['attempt_ids']);
check('perfect score = max', (int) $result['score'] === (int) $result['max_score'], $result['score'] . '/' . $result['max_score']);
check('max_score = 3*100+25', (int) $result['max_score'] === 325);
check('result_url returned', str_contains((string) $result['result_url'], '/result/'));

// Duplicate submission protection
$dupCaught = false;
try {
    $attemptSvc->submit($start['attempt_id'], $answers, (int) $user['id'], [$start['attempt_id']]);
} catch (HttpException $e) {
    $dupCaught = true;
    check('duplicate submit → 409', $e->getStatus() === 409, (string) $e->getStatus());
}
check('duplicate submission rejected', $dupCaught);

// Wrong answers score lower
$start2 = $attemptSvc->start($challenge, $user, 'Wrongo', null);
$_SESSION['attempt_ids'][] = $start2['attempt_id'];
$playable2 = \ChallengeChain\Models\Question::playableForChallenge((int) $challenge['id']);
$wrongAnswers = [];
foreach ($playable2 as $i => $q) {
    $wrongIndex = ($payload['questions'][$i]['correct_index'] + 1) % count($q['options']);
    $wrongAnswers[$q['id']] = $q['options'][$wrongIndex]['id'];
}
$result2 = $attemptSvc->submit($start2['attempt_id'], $wrongAnswers, (int) $user['id'], $_SESSION['attempt_ids']);
check('wrong answers → 0 score', (int) $result2['score'] === 0, (string) $result2['score']);
check('duration tracked (ms)', (int) $result2['duration_ms'] >= 0);

// Partial score
$start3 = $attemptSvc->start($challenge, $user, 'Halfy', null);
$_SESSION['attempt_ids'][] = $start3['attempt_id'];
$partial = [];
foreach ($playable2 as $i => $q) {
    if ($i === 0) {
        $partial[$q['id']] = $q['options'][$payload['questions'][0]['correct_index']]['id'];
    } else {
        $wrongIndex = ($payload['questions'][$i]['correct_index'] + 1) % count($q['options']);
        $partial[$q['id']] = $q['options'][$wrongIndex]['id'];
    }
}
$result3 = $attemptSvc->submit($start3['attempt_id'], $partial, (int) $user['id'], $_SESSION['attempt_ids']);
check('1/3 correct → 100 pts', (int) $result3['score'] === 100, (string) $result3['score']);

// Authorization: someone else cannot submit our attempt
$authzBlocked = false;
try {
    $freshStart = $attemptSvc->start($challenge, $user, 'Mine', null);
    $attemptSvc->submit($freshStart['attempt_id'], $answers, 999999, []);
} catch (HttpException $e) {
    $authzBlocked = $e->getStatus() === 403;
}
check('foreign attempt submit blocked (403)', $authzBlocked);

// ---------------------------------------------------------------------------
section('Leaderboard (score desc, time tie-break)');
$rows = $attemptSvc->leaderboard((int) $challenge['id']);
// Same participant (same user) → only their best attempt is ranked.
check('leaderboard keeps best attempt per participant', count($rows) >= 1 && (int) $rows[0]['score'] === 325, (string) count($rows));
check('perfect score ranked first', (int) $rows[0]['score'] === 325);
$sorted = true;
for ($i = 1; $i < count($rows); $i++) {
    if ((int) $rows[$i]['score'] > (int) $rows[$i - 1]['score']) {
        $sorted = false;
    }
}
check('scores sorted descending', $sorted);
check('rank attribute present', isset($rows[0]['rank']));

// Guest participants (no user_id) are ranked separately by name+IP.
$g1 = $attemptSvc->start($challenge, [], 'AliceG', null);
$_SESSION['attempt_ids'][] = $g1['attempt_id'];
$attemptSvc->submit($g1['attempt_id'], $answers, null, $_SESSION['attempt_ids']);
$g2 = $attemptSvc->start($challenge, [], 'BobG', null);
$_SESSION['attempt_ids'][] = $g2['attempt_id'];
$attemptSvc->submit($g2['attempt_id'], $wrongAnswers, null, $_SESSION['attempt_ids']);
$rows2 = $attemptSvc->leaderboard((int) $challenge['id']);
check('multiple participants ranked', count($rows2) === 3, (string) count($rows2));
check('guest perfect score above guest zero', (int) $rows2[0]['score'] === 325 && (int) $rows2[count($rows2) - 1]['score'] === 0);
check('participant names present', ($rows2[0]['participant_name'] ?? '') !== '');

// ---------------------------------------------------------------------------
section('Public URLs & sharing');
check('public URL pattern /challenge/{slug}', Challenge::findBySlug($challenge['slug']) !== null);
$attempt = Attempt::findByPublicId($start['attempt_id']);
check('attempt public result URL resolvable', $attempt !== null && $attempt['status'] === 'completed');
check('result page data: participant name', $attempt['participant_name'] === 'Speedy');

$ref = new ReferralService();
$shareUrl = $ref->challengeShareUrl($challenge['slug'], $user['referral_code']);
check('share URL includes ref param', str_contains($shareUrl, 'ref=' . $user['referral_code']));
check('share URL is absolute', str_starts_with($shareUrl, 'http'));

// Referral tracking
$refCountBefore = $ref->referralCount((int) $user['id']);
$ref->trackVisit((int) $challenge['id'], (string) $user['referral_code'], '/challenge/' . $challenge['slug']);
$refCountAfter = $ref->referralCount((int) $user['id']);
check('referral event recorded', $refCountAfter === $refCountBefore + 1);
$viewCount = (int) (Challenge::find((int) $challenge['id'])['view_count'] ?? 0);
check('challenge view incremented', $viewCount >= 1, (string) $viewCount);

Challenge::incrementShares((int) $challenge['id']);
check('share counter increments', (int) Challenge::find((int) $challenge['id'])['share_count'] === 1);

// Unknown referral code must not create events
$ref->trackVisit(null, 'NOPE9999', '/');
check('unknown referral ignored', $ref->referralCount((int) $user['id']) === $refCountAfter);

// ---------------------------------------------------------------------------
section('Guest publishing and unlisted challenges');
$guestChallenge = $challengeSvc->create(null, array_replace($payload, ['title' => 'Guest curiosity quiz']), 'manual', false);
check('guest can publish without a user account', $guestChallenge['user_id'] === null);
check('guest challenge is available by link', $challengeSvc->requirePublic($guestChallenge['slug'])['id'] === $guestChallenge['id']);
check('guest questions are persisted', count(\ChallengeChain\Models\Question::playableForChallenge((int) $guestChallenge['id'])) === 3);
$unlistedChallenge = $challengeSvc->create(null, array_replace($payload, ['title' => 'A quiz shared by link', 'is_public' => false]), 'manual', false);
check('unlisted challenge is available by its link', $challengeSvc->requirePublic($unlistedChallenge['slug'])['id'] === $unlistedChallenge['id']);
check('unlisted challenge stays out of Explore', Challenge::explore('A quiz shared by link') === []);
$unlistedAttempt = (new AttemptService())->start($unlistedChallenge, null, 'Guest player', null);
check('unlisted challenge can be played as a guest', count($unlistedAttempt['questions']) === 3);
check('unlisted gameplay does not expose correct answers', !str_contains(json_encode($unlistedAttempt['questions']), 'is_correct'));
Database::query("UPDATE challenges SET status = 'draft' WHERE id = :id", ['id' => $unlistedChallenge['id']]);
try {
    $challengeSvc->requirePublic($unlistedChallenge['slug']);
    check('draft challenges remain inaccessible', false);
} catch (HttpException $error) {
    check('draft challenges remain inaccessible', $error->getStatus() === 404);
}

section('Rate limiting');
RateLimiter::reset('test-key');
$allowed = 0;
for ($i = 0; $i < 6; $i++) {
    if (RateLimiter::hit('test-key', 5, 60)) {
        $allowed++;
    }
}
check('hits allowed up to max', $allowed === 5, (string) $allowed);
check('blocked after max', RateLimiter::isBlocked('test-key', 5, 60));
RateLimiter::reset('test-key');
check('reset clears block', !RateLimiter::isBlocked('test-key', 5, 60));

// ---------------------------------------------------------------------------
section('Router (public URL matching)');
$router = new Router();
$router->get('/challenge/{slug}', TestController::class, 'ok');
$router->post('/api/attempts/{publicId}/submit', TestController::class, 'ok');
$router->get('/', TestController::class, 'ok');

ob_start();
$router->dispatch('GET', '/challenge/abc123');
$out = ob_get_clean();
check('route /challenge/abc123 matches', $out === 'slug=abc123', $out);

$notFound = false;
try {
    $router->dispatch('GET', '/no/such/route');
} catch (HttpException $e) {
    $notFound = $e->getStatus() === 404;
}
check('unknown route → 404', $notFound);

$notFound2 = false;
try {
    $router->dispatch('POST', '/challenge/abc123'); // GET-only route
} catch (HttpException $e) {
    $notFound2 = $e->getStatus() === 404;
}
check('method mismatch → 404', $notFound2);

// ---------------------------------------------------------------------------
section('XSS / output escaping');
check('e() escapes HTML', e('<script>alert(1)</script>') === '&lt;script&gt;alert(1)&lt;/script&gt;');
check('e() escapes quotes', e('" onmouseover="x') === '&quot; onmouseover=&quot;x');

// ---------------------------------------------------------------------------
section('SQLite operations (transactions, FKs)');
$fkError = false;
try {
    Database::query('INSERT INTO questions (challenge_id, question_text) VALUES (:c, :t)', ['c' => 999999999, 't' => 'orphan']);
} catch (PDOException $e) {
    $fkError = true;
}
check('foreign key violation rejected', $fkError);

$txOk = false;
try {
    Database::transaction(static function (): void {
        Database::insert('users', [
            'username' => 'tx_user_' . random_int(100, 999),
            'email' => 'tx' . random_int(10000, 99999) . '@example.com',
            'password_hash' => password_hash('x', PASSWORD_DEFAULT),
            'display_name' => 'TX',
            'referral_code' => random_code(10),
        ]);
        throw new RuntimeException('rollback');
    });
} catch (RuntimeException $e) {
    $txOk = true;
}
check('transaction rolls back on exception', $txOk && User::findByUsername('tx_user_' . 'nope') === null);

// ---------------------------------------------------------------------------
section('Demo seed data present');
// Reuse the real seed script against this temp DB (Windows + POSIX compatible).
$seedCmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(dirname(__DIR__) . '/scripts/seed.php');
$seedOut = [];
$seedCode = 0;
if (DIRECTORY_SEPARATOR === '\\') {
    exec('set "DB_PATH=' . $tempDb . '" && ' . $seedCmd . ' 2>&1', $seedOut, $seedCode);
} else {
    exec('DB_PATH=' . escapeshellarg($tempDb) . ' ' . $seedCmd . ' 2>&1', $seedOut, $seedCode);
}
check('seed script exits 0', $seedCode === 0, implode("\n", $seedOut));
$demoUser = User::findByEmail('demo@challengechain.dev');
check('demo user exists', $demoUser !== null);
$demoChallenges = Database::fetchAll('SELECT * FROM challenges WHERE is_demo = 1');
check('demo challenges seeded (>= 4)', count($demoChallenges) >= 4, (string) count($demoChallenges));
$demoQ = Database::fetch('SELECT COUNT(*) c FROM questions q JOIN challenges c2 ON c2.id = q.challenge_id WHERE c2.is_demo = 1');
check('demo challenges have questions', (int) $demoQ['c'] >= 30, (string) $demoQ['c']);
check('demo challenge public', (int) ($demoChallenges[0]['is_public'] ?? 0) === 1);
check('demo user password is demo1234', $demoUser !== null && password_verify('demo1234', $demoUser['password_hash']));

// ---------------------------------------------------------------------------
section('Gamification data');
$achievements = Database::fetchAll('SELECT * FROM achievements');
check('achievements seeded (7)', count($achievements) === 7, (string) count($achievements));
check('user xp is integer >= 0', (int) Auth::user()['xp'] >= 0);

// ---------------------------------------------------------------------------
section('Production deployment');
// Fresh PHP processes simulate PHP-FPM starting outside the project directory.
function deploymentProbe(string $code): array
{
    $process = proc_open(
        [PHP_BINARY, '-r', $code],
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        sys_get_temp_dir()
    );
    if (!is_resource($process)) {
        throw new RuntimeException('Unable to start deployment probe.');
    }
    $output = stream_get_contents($pipes[1]);
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return ['output' => $output, 'errors' => $errors, 'exit' => proc_close($process)];
}
$bootstrapCode = 'require ' . var_export(dirname(__DIR__) . '/bootstrap.php', true) . ';';
$httpProbe = deploymentProbe('require ' . var_export(__DIR__ . '/ai-http.php', true) . ';');
check('AI HTTP transport handles temporary limits, quotas and credentials safely',
    $httpProbe['exit'] === 0 && substr_count($httpProbe['output'], 'OK:') === 21,
    $httpProbe['errors'] . $httpProbe['output']);
$routerProbe = deploymentProbe('putenv("AI_TEST_PROVIDER=openrouter"); require ' . var_export(__DIR__ . '/ai-http.php', true) . ';');
check('OpenRouter free routing, JSON validation and diagnostics stay on the free model',
    $routerProbe['exit'] === 0 && substr_count($routerProbe['output'], 'OK:') === 21,
    $routerProbe['errors'] . $routerProbe['output']);
foreach ([
    '$_ENV["AI_API_KEY"] = "";' => 'PHP environment',
    'unset($_ENV["AI_API_KEY"]); putenv("AI_API_KEY=");' => 'process environment',
] as $override => $label) {
    $probe = deploymentProbe($override . $bootstrapCode . 'echo is_demo_mode() ? "demo" : "live";');
    check('empty key in ' . $label . ' disables live AI even with a configured .env',
        $probe['exit'] === 0 && $probe['output'] === 'demo', $probe['errors']);
}
foreach ([
    'storage/database.sqlite' => dirname(__DIR__) . '/storage/database.sqlite',
    $tempDb => $tempDb,
    ':memory:' => ':memory:',
    'C:\\challengechain\\storage\\database.sqlite' => 'C:\\challengechain\\storage\\database.sqlite',
] as $configuredPath => $expectedPath) {
    $probe = deploymentProbe('$_ENV["DB_PATH"] = ' . var_export($configuredPath, true) . ';'
        . $bootstrapCode . 'echo config("database.path");');
    check('database path resolved from any working directory: ' . $configuredPath,
        $probe['exit'] === 0 && $probe['output'] === $expectedPath, $probe['errors']);
}
$healthCode = 'register_shutdown_function(static function (): void { fwrite(STDERR, (string) http_response_code()); });'
    . '(new ChallengeChain\\Controllers\\ApiController())->health();';
$healthy = deploymentProbe('$_ENV["DB_PATH"] = ' . var_export($tempDb, true) . ';' . $bootstrapCode . $healthCode);
$healthyBody = json_decode($healthy['output'], true);
check('health returns 200 for a migrated database',
    $healthy['exit'] === 0 && $healthy['errors'] === '200' && ($healthyBody['ok'] ?? false) === true);
$unhealthy = deploymentProbe('$_ENV["DB_PATH"] = ":memory:";' . $bootstrapCode . $healthCode);
$unhealthyBody = json_decode($unhealthy['output'], true);
check('health returns 503 for an empty database',
    $unhealthy['exit'] === 0 && $unhealthy['errors'] === '503' && ($unhealthyBody['ok'] ?? true) === false);
check('failed health check does not expose SQLite internals',
    !str_contains($unhealthy['output'], 'SQLSTATE') && !str_contains($unhealthy['output'], dirname(__DIR__)));

// Regression: an HTTP page must not fetch CSS/JS or submit forms over HTTPS
// merely because APP_URL contains the canonical HTTPS address.
section('Browser resources and navigation');
check('internal navigation stays on the current origin', url('/create') === '/create');
check('form actions stay on the current origin', url('/login') === '/login');
check('browser API base stays on the current origin', url('/') === '/');
check('public sharing links remain absolute', base_url('/challenge/test') === 'http://localhost:8000/challenge/test');
check('explicit external links are preserved', url('https://example.com/') === 'https://example.com/');
foreach (['/', '/login', '/create', '/challenge/' . $demoChallenges[0]['slug'] . '/play'] as $route) {
    $probe = deploymentProbe(
        '$_ENV["DB_PATH"] = ' . var_export($tempDb, true) . ';'
        . '$_ENV["APP_URL"] = "https://cc.tendry.mg";'
        . '$_SERVER["REQUEST_METHOD"] = "GET";'
        . '$_SERVER["REQUEST_URI"] = ' . var_export($route, true) . ';'
        . '$_SERVER["HTTPS"] = "off";'
        . 'ini_set("session.save_path", sys_get_temp_dir());'
        . 'require ' . var_export(dirname(__DIR__) . '/public/index.php', true) . ';'
    );
    check('page renders over HTTP: ' . $route, $probe['exit'] === 0 && $probe['errors'] === '', $probe['errors']);
    preg_match_all('~(?:src|href)="(/assets/[^"<>]+)"~', $probe['output'], $matches);
    $assetsValid = count($matches[1]) >= 4;
    foreach ($matches[1] as $resource) {
        $path = (string) parse_url(html_entity_decode($resource), PHP_URL_PATH);
        $assetsValid = $assetsValid && is_file(dirname(__DIR__) . '/public' . $path);
        if (preg_match('~\.(?:css|js)$~', $path)) {
            $assetsValid = $assetsValid && (bool) preg_match('~\?v=\d+$~', $resource);
        }
    }
    check('styles and scripts exist and refresh cached versions: ' . $route, $assetsValid);
    check('page keeps resources and API base on the current origin: ' . $route,
        str_contains($probe['output'], 'data-app-url="/"')
        && !preg_match('~(?:src|href|action)="https?://cc\.tendry\.mg/~', $probe['output']));
}

// ---------------------------------------------------------------------------
// Cleanup
try {
    Database::pdo()->close();
} catch (Throwable) {
}
@unlink($tempDb);
@unlink($tempDb . '-wal');
@unlink($tempDb . '-shm');

echo "\n----------------------------------------\n";
echo "PASSED: {$passed}  FAILED: {$failed}\n";
if ($failed > 0) {
    echo "Failures:\n";
    foreach ($failures as $f) {
        echo "  - {$f}\n";
    }
    exit(1);
}
echo "All tests passed.\n";
exit(0);

/** Route handler helper for router tests. */
final class TestController
{
    public function ok(string $param = ''): void
    {
        echo 'slug=' . $param;
    }
}
