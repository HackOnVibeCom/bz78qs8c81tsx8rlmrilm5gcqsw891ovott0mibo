<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require dirname(__DIR__) . '/bootstrap.php';

use ChallengeChain\Core\HttpException;
use ChallengeChain\Services\AIDiagnostics;
use ChallengeChain\Services\AIService;

if (is_demo_mode()) {
    fwrite(STDERR, "Aucune cle IA configuree dans .env.\n");
    exit(1);
}

echo 'Modele : ' . config('ai.model') . PHP_EOL;
$provider = (string) config('ai.provider_name', 'Le service IA');
$accountUrl = (string) config('ai.account_url', '');
if (in_array('--diagnostic', $argv, true)) {
    $diagnostics = new AIDiagnostics();
    $models = $diagnostics->models();
    echo 'Acces aux modeles : ' . json_encode($models, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    if ($models['status'] === 0 || in_array($models['status'], [401, 403], true)) {
        fwrite(STDERR, "La connexion ou l'acces a la cle doit etre corrige avant de tester la generation.\n");
        exit(1);
    }
    // Avoid making the diagnostic itself a burst against a low RPS allowance.
    usleep(1_100_000);
    $completion = $diagnostics->completion();
    echo 'Requete courte (8 tokens maximum) : ' . json_encode($completion, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    if ($completion['status'] >= 200 && $completion['status'] < 300) {
        echo "La requete courte est acceptee. Le quiz complet reste a verifier separement.\n";
        exit(0);
    }
    if ($completion['status'] === 429) {
        if (($completion['headers']['x-ratelimit-limit-req-minute'] ?? null) === '0') {
            fwrite(STDERR, "Diagnostic confirme : $provider autorise 0 requete de generation par minute pour ce modele avec ce compte.\n");
            fwrite(STDERR, "L'acces aux modeles peut fonctionner pendant que la generation est bloquee. Les nouvelles tentatives ne leveront pas une limite fixee a zero.\n");
            fwrite(STDERR, "Verifiez l'acces API et les limites du compte : $accountUrl\n");
            fwrite(STDERR, "Si cette limite ne peut pas etre modifiee dans le compte, contactez le support $provider.\n");
        } else {
            fwrite(STDERR, "Le 429 concerne aussi une requete de 8 tokens. Consultez le motif ci-dessus et les limites du compte $provider : $accountUrl\n");
        }
        if ($provider === 'OpenRouter') {
            fwrite(STDERR, "Les modeles gratuits ont des quotas par minute et par jour. Verifiez le quota gratuit avant de relancer.\n");
        } elseif ($provider === 'Mistral') {
            fwrite(STDERR, "Verifiez aussi l'origine de la cle : Studio > API Keys ou Vibe > Code CLI, dont les budgets sont distincts.\n");
        }
    }
    exit(1);
}
$options = getopt('', ['diagnostic', 'questions:', 'topic:', 'language:', 'category:', 'difficulty:']) ?: [];
$questionCount = filter_var($options['questions'] ?? 3, FILTER_VALIDATE_INT, ['options' => ['min_range' => 3, 'max_range' => 15]]);
if ($questionCount === false) {
    fwrite(STDERR, "Utilisez --questions=N avec N compris entre 3 et 15.\n");
    exit(2);
}
echo 'Questions demandees : ' . $questionCount . PHP_EOL;
try {
    $challenge = (new AIService())->generate([
        'category' => $options['category'] ?? 'general',
        'topic' => $options['topic'] ?? 'Les capitales du monde',
        'difficulty' => $options['difficulty'] ?? 'easy',
        'question_count' => $questionCount,
        'language' => $options['language'] ?? 'fr',
    ]);
} catch (HttpException $e) {
    fwrite(STDERR, 'Verification IA echouee : ' . $e->getMessage() . PHP_EOL);
    if ($e->getStatus() === 429) {
        fwrite(STDERR, "Pour afficher le motif $provider sans la cle : php " . __FILE__ . " --diagnostic\n");
        fwrite(STDERR, "Verifiez les quotas du compte $provider : $accountUrl\n");
        fwrite(STDERR, "Si le quota gratuit quotidien est epuise, attendez sa reinitialisation avant de relancer.\n");
    }
    exit(1);
}

if ($challenge['demo'] || count($challenge['questions']) !== $questionCount) {
    fwrite(STDERR, "La reponse IA ne contient pas les $questionCount questions attendues.\n");
    exit(1);
}
echo "$provider OK : $questionCount questions generees et validees, aucune donnee publiee.\n";
