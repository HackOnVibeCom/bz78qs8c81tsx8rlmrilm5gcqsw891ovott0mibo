<?php

declare(strict_types=1);

// Exercise the real AI service with a fake transport; never call a provider.
namespace ChallengeChain\Services {
    function curl_init(string $url): object
    {
        $handle = (object) ['url' => $url, 'options' => [], 'response' => []];
        $GLOBALS['ai_http_test']['requests'][] = $handle;
        return $handle;
    }

    function curl_setopt_array(object $handle, array $options): bool
    {
        $handle->options = $options;
        return true;
    }

    function curl_exec(object $handle): string|false
    {
        $response = array_shift($GLOBALS['ai_http_test']['responses']);
        if ($response === null) {
            throw new \RuntimeException('Unexpected extra AI request.');
        }
        $handle->response = $response;
        foreach ($response['headers'] ?? [] as $header) {
            ($handle->options[CURLOPT_HEADERFUNCTION])($handle, $header . "\r\n");
        }
        return $response['body'];
    }

    function curl_errno(object $handle): int { return $handle->response['errno'] ?? 0; }
    function curl_error(object $handle): string { return $handle->response['network_error'] ?? ''; }
    function curl_getinfo(object $handle, int $option): int { return $handle->response['status']; }
    function curl_close(object $handle): void {}
    function usleep(int $microseconds): void { $GLOBALS['ai_http_test']['delays'][] = $microseconds; }
}

namespace {
    if (PHP_SAPI !== 'cli') {
        http_response_code(403);
        exit('CLI only');
    }

    $_ENV['AI_API_KEY'] = 'fixture-key-not-a-real-secret';
    $openRouterTest = getenv('AI_TEST_PROVIDER') === 'openrouter';
    $expectedBaseUrl = $openRouterTest ? 'https://openrouter.ai/api/v1' : 'https://api.mistral.ai/v1';
    $expectedModel = $openRouterTest ? 'openrouter/free' : 'mistral-small-latest';
    $expectedProvider = $openRouterTest ? 'OpenRouter' : 'Mistral';
    $_ENV['AI_BASE_URL'] = $expectedBaseUrl;
    $_ENV['AI_MODEL'] = $expectedModel;
    $_ENV['AI_TIMEOUT'] = '45';
    require dirname(__DIR__) . '/bootstrap.php';

    use ChallengeChain\Core\HttpException;
    use ChallengeChain\Services\AIDiagnostics;
    use ChallengeChain\Services\AIService;

    function ensure(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }

    function prepareResponses(array $responses): void
    {
        $GLOBALS['ai_http_test'] = ['responses' => $responses, 'requests' => [], 'delays' => []];
    }

    function generateFixture(int $questionCount = 3): array
    {
        return (new AIService())->generate(['topic' => 'Test transport', 'question_count' => $questionCount]);
    }

    function expectFailure(int $status): HttpException
    {
        try {
            generateFixture();
        } catch (HttpException $e) {
            ensure($e->getStatus() === $status, 'Incorrect HTTP status.');
            ensure(!str_contains($e->getMessage(), 'fixture-key-not-a-real-secret'), 'Credential leaked in error.');
            return $e;
        }
        throw new RuntimeException('Expected an AI error, not a demo fallback.');
    }

    $quiz = [
        'title' => 'Transport test',
        'questions' => array_map(static fn (int $number): array => [
            'question' => "What is $number + 1?",
            'options' => [(string) ($number + 1), '0', '99', '100'],
            'correct_index' => 0,
        ], [1, 2, 3]),
    ];
    $success = ['status' => 200, 'body' => json_encode([
        'choices' => [['message' => ['content' => json_encode($quiz, JSON_THROW_ON_ERROR)]]],
    ], JSON_THROW_ON_ERROR)];
    $limited = ['status' => 429, 'body' => json_encode([
        'message' => 'Rate limited: fixture-key-not-a-real-secret', 'type' => 'rate_limit_error',
    ], JSON_THROW_ON_ERROR)];

    prepareResponses([$limited + ['headers' => ['Retry-After: 2']], $success]);
    $result = generateFixture();
    ensure(!$result['demo'] && count($result['questions']) === 3, 'Retry did not generate a real AI response.');
    ensure(count($GLOBALS['ai_http_test']['requests']) === 2, 'Incorrect retry count.');
    ensure($GLOBALS['ai_http_test']['delays'] === [2_000_000], 'Retry-After was not respected.');
    $request = $GLOBALS['ai_http_test']['requests'][0];
    $payload = json_decode($request->options[CURLOPT_POSTFIELDS], true);
    ensure($request->url === $expectedBaseUrl . '/chat/completions', 'Incorrect endpoint.');
    ensure($payload['model'] === $expectedModel && !isset($payload['models']), 'Unexpected model or paid fallback configured.');
    ensure(($payload['response_format']['type'] ?? null) === 'json_object', 'JSON mode missing.');
    ensure(in_array('Authorization: Bearer fixture-key-not-a-real-secret', $request->options[CURLOPT_HTTPHEADER], true), 'Bearer authentication missing.');
    ensure($payload['max_tokens'] > 0 && $payload['max_tokens'] < 8192, 'Missing output token bound.');
    echo "OK: temporary 429, Retry-After, valid quiz and bounded tokens\n";

    prepareResponses([$limited, $limited, $limited]);
    $error = expectFailure(429);
    ensure(str_contains($error->getMessage(), $expectedProvider), 'Error refers to the wrong provider.');
    if ($openRouterTest) {
        ensure(!str_contains($error->getMessage(), 'Mistral'), 'OpenRouter error mentions the old provider.');
    }
    $delays = $GLOBALS['ai_http_test']['delays'];
    ensure(count($GLOBALS['ai_http_test']['requests']) === 3 && count($delays) === 2, 'Retries are not bounded.');
    ensure($delays[0] >= 1_000_000 && $delays[0] <= 1_250_000
        && $delays[1] >= 2_000_000 && $delays[1] <= 2_250_000, 'Backoff is not exponential.');
    echo "OK: persistent 429 stops after two retries without demo fallback\n";

    prepareResponses([$limited + ['headers' => ['X-RateLimit-Limit-Req-Minute: 0', 'X-RateLimit-Remaining-Req-Minute: 0', 'Retry-After: 1']]]);
    $error = expectFailure(429);
    ensure(count($GLOBALS['ai_http_test']['requests']) === 1 && !$GLOBALS['ai_http_test']['delays'], 'Zero allocated request limit was retried.');
    ensure(str_contains($error->getMessage(), '0 requete'), 'Zero allocated limit must be identified explicitly.');
    echo "OK: zero allocated request limit fails directly with account guidance\n";

    prepareResponses([$limited + ['headers' => ['X-RateLimit-Limit-Req-Minute: 60', 'X-RateLimit-Remaining-Req-Minute: 0', 'Retry-After: 0']], $success]);
    generateFixture();
    ensure(count($GLOBALS['ai_http_test']['requests']) === 2, 'Exhausted remaining allowance was confused with a zero allocated limit.');
    echo "OK: zero remaining requests with a positive allowance can still retry\n";

    prepareResponses([['status' => 429, 'body' => json_encode([
        'error' => ['code' => 'insufficient_quota', 'message' => 'Monthly spending limit reached'],
    ], JSON_THROW_ON_ERROR)]]);
    $error = expectFailure(429);
    ensure(count($GLOBALS['ai_http_test']['requests']) === 1 && !$GLOBALS['ai_http_test']['delays'], 'Exhausted quota was retried.');
    ensure(str_contains($error->getMessage(), 'plafond'), 'Quota guidance missing.');
    echo "OK: exhausted quota fails directly with account guidance\n";

    prepareResponses([$limited + ['headers' => ['Retry-After: 120']]]);
    $error = expectFailure(429);
    ensure(!$GLOBALS['ai_http_test']['delays'] && str_contains($error->getMessage(), '120 secondes'), 'Long Retry-After was shortened.');
    echo "OK: long Retry-After does not block the web request\n";

    prepareResponses([$limited + ['headers' => ['Retry-After: ' . gmdate('D, d M Y H:i:s', time() + 120) . ' GMT']]]);
    expectFailure(429);
    ensure(!$GLOBALS['ai_http_test']['delays'], 'HTTP-date Retry-After was ignored.');
    echo "OK: Retry-After HTTP date is respected\n";

    prepareResponses([['status' => 429, 'body' => 'not JSON', 'headers' => ['Retry-After: invalid']], $success]);
    generateFixture();
    ensure(count($GLOBALS['ai_http_test']['delays']) === 1, 'Invalid error body/header broke fallback backoff.');
    echo "OK: malformed error details use bounded backoff\n";

    prepareResponses([['status' => 401, 'body' => 'Invalid fixture-key-not-a-real-secret']]);
    expectFailure(502);
    ensure(count($GLOBALS['ai_http_test']['requests']) === 1, 'Authentication failure was retried.');
    echo "OK: credentials rejected without retry or leakage\n";

    prepareResponses([['status' => 500, 'body' => 'Provider unavailable']]);
    expectFailure(502);
    ensure(count($GLOBALS['ai_http_test']['requests']) === 1, 'Other provider error was treated as a rate limit.');
    echo "OK: other provider errors remain explicit\n";

    prepareResponses([['status' => 200, 'body' => json_encode([
        'choices' => [['message' => ['content' => 'Not valid quiz JSON']]],
    ], JSON_THROW_ON_ERROR)], $success]);
    generateFixture();
    ensure(count($GLOBALS['ai_http_test']['requests']) === 2, 'Malformed model output was not retried once.');
    $strictPayload = json_decode($GLOBALS['ai_http_test']['requests'][1]->options[CURLOPT_POSTFIELDS], true);
    ensure(str_contains($strictPayload['messages'][0]['content'], 'previous reply failed validation'), 'Malformed output retry is not stricter.');
    ensure($strictPayload['model'] === $expectedModel && !isset($strictPayload['models']), 'Validation retry changed models or added a paid fallback.');
    echo "OK: malformed output retries once with the same configured model\n";

    $fiveQuiz = $quiz;
    $fiveQuiz['questions'] = array_merge($quiz['questions'], array_slice($quiz['questions'], 0, 2));
    $fiveSuccess = ['status' => 200, 'body' => json_encode([
        'choices' => [['finish_reason' => 'stop', 'message' => ['content' => json_encode($fiveQuiz, JSON_THROW_ON_ERROR)]]],
    ], JSON_THROW_ON_ERROR)];
    $reasoningOnly = ['status' => 200, 'body' => json_encode([
        'model' => 'fixture/free',
        'choices' => [['finish_reason' => 'length', 'message' => [
            'content' => null, 'reasoning' => 'private-reasoning-must-never-be-used-as-quiz',
        ]]],
        'usage' => ['completion_tokens' => 3072, 'completion_tokens_details' => ['reasoning_tokens' => 3072]],
    ], JSON_THROW_ON_ERROR)];
    prepareResponses([$reasoningOnly, $fiveSuccess]);
    $result = generateFixture(5);
    ensure(count($result['questions']) === 5 && !$result['demo'], 'Website-sized quiz did not recover from reasoning-only output.');
    $requests = $GLOBALS['ai_http_test']['requests'];
    $initialPayload = json_decode($requests[0]->options[CURLOPT_POSTFIELDS], true);
    $retryPayload = json_decode($requests[1]->options[CURLOPT_POSTFIELDS], true);
    ensure($retryPayload['max_tokens'] > $initialPayload['max_tokens'], 'Truncated response retried with the same insufficient budget.');
    ensure($retryPayload['model'] === $expectedModel && !isset($retryPayload['models']), 'Truncation recovery added a paid fallback.');
    if ($openRouterTest) {
        ensure(($initialPayload['reasoning']['max_tokens'] ?? null) === 256, 'OpenRouter reasoning budget is not capped.');
        ensure($initialPayload['reasoning']['max_tokens'] < $initialPayload['max_tokens'] / 4, 'No budget left for visible quiz JSON.');
    } else {
        ensure(!isset($initialPayload['reasoning']), 'OpenRouter-specific reasoning options leaked to another provider.');
    }
    echo "OK: five-question website flow recovers from reasoning-only truncation with a larger budget\n";

    $empty = ['status' => 200, 'body' => '{"choices":[{"message":{"content":""},"finish_reason":"stop"}]}'];
    prepareResponses([$empty, $empty]);
    $error = expectFailure(502);
    ensure(count($GLOBALS['ai_http_test']['requests']) === 2, 'Empty response retries are not bounded.');
    ensure(!str_contains($error->getMessage(), 'private-reasoning'), 'Reasoning text leaked into the response.');
    echo "OK: persistent empty output fails safely after one retry\n";

    prepareResponses([$success, $fiveSuccess]);
    $result = generateFixture(5);
    ensure(count($result['questions']) === 5 && count($GLOBALS['ai_http_test']['requests']) === 2, 'CLI-sized output was accepted for a five-question website request.');
    echo "OK: three-question CLI result is rejected when the website requested five\n";

    $previousBudget = 0;
    foreach ([3, 5, 8, 10, 15] as $count) {
        $fullQuiz = $quiz;
        $fullQuiz['questions'] = array_map(static fn (int $index): array => $quiz['questions'][$index % 3], range(0, $count - 1));
        prepareResponses([['status' => 200, 'body' => json_encode([
            'choices' => [['message' => ['content' => json_encode($fullQuiz, JSON_THROW_ON_ERROR)], 'finish_reason' => 'stop']],
        ], JSON_THROW_ON_ERROR)]]);
        $result = generateFixture($count);
        $requestPayload = json_decode($GLOBALS['ai_http_test']['requests'][0]->options[CURLOPT_POSTFIELDS], true);
        ensure(count($result['questions']) === $count, 'Incorrect question count for a supported website setting.');
        ensure($requestPayload['max_tokens'] > $previousBudget && $requestPayload['max_tokens'] <= 16384, 'Output budget does not scale safely with the quiz.');
        $previousBudget = $requestPayload['max_tokens'];
    }
    echo "OK: all website question counts generate complete quizzes with bounded output\n";

    $diagnostics = new AIDiagnostics();
    prepareResponses([['status' => 200, 'body' => json_encode(['data' => [['id' => 'model-a'], ['id' => 'model-b']]], JSON_THROW_ON_ERROR)]]);
    $report = $diagnostics->models();
    $request = $GLOBALS['ai_http_test']['requests'][0];
    ensure($report['status'] === 200 && $report['model_count'] === 2, 'Models diagnostic failed.');
    ensure($request->url === $expectedBaseUrl . '/models' && empty($request->options[CURLOPT_POST]), 'Models diagnostic must be a read request.');
    ensure(!isset($report['data']), 'Full model response was unnecessarily exposed.');
    echo "OK: model access is checked without generating tokens\n";

    prepareResponses([['status' => 429, 'body' => json_encode([
        'message' => 'Rate limited fixture-key-not-a-real-secret', 'code' => 1300, 'type' => 'rate_limited',
    ], JSON_THROW_ON_ERROR), 'headers' => ['Retry-After: 60', 'X-RateLimit-Remaining: 0', 'X-Request-Id: diagnostic-id', 'Set-Cookie: private-value']]]);
    $report = $diagnostics->completion();
    $encodedReport = json_encode($report, JSON_THROW_ON_ERROR);
    ensure($report['status'] === 429 && $report['details']['root.code'] === '1300', 'Provider reason is missing.');
    ensure(!str_contains($encodedReport, 'fixture-key-not-a-real-secret') && str_contains($encodedReport, 'CLE MASQUEE'), 'Diagnostic leaked the configured key.');
    ensure(!str_contains($encodedReport, 'private-value') && $report['headers']['retry-after'] === '60', 'Diagnostic header filtering failed.');
    ensure(count($GLOBALS['ai_http_test']['requests']) === 1 && !$GLOBALS['ai_http_test']['delays'], 'Diagnostic retried a blocked request.');
    echo "OK: exact rate limit reason is retained while keys and cookies are hidden\n";

    prepareResponses([$success]);
    $report = $diagnostics->completion();
    $request = $GLOBALS['ai_http_test']['requests'][0];
    $payload = json_decode($request->options[CURLOPT_POSTFIELDS], true);
    ensure($report['status'] === 200 && $payload['max_tokens'] === 8, 'Diagnostic generation is not minimal.');
    ensure($payload['model'] === $expectedModel && count($payload['messages']) === 1, 'Diagnostic changed the selected model.');
    echo "OK: diagnostic completion stays within eight output tokens\n";

    prepareResponses([['status' => 401, 'body' => '{"detail":"Invalid fixture-key-not-a-real-secret"}']]);
    $report = $diagnostics->models();
    ensure($report['status'] === 401 && !str_contains(json_encode($report), 'fixture-key-not-a-real-secret'), 'Diagnostic did not preserve authentication status safely.');
    echo "OK: authentication diagnostics retain the upstream status\n";

    prepareResponses([['status' => 0, 'body' => false, 'errno' => 6, 'network_error' => 'DNS failure fixture-key-not-a-real-secret']]);
    $report = $diagnostics->models();
    ensure($report['status'] === 0 && str_contains($report['network_error'], 'DNS failure'), 'Network diagnostic failed.');
    ensure(!str_contains($report['network_error'], 'fixture-key-not-a-real-secret'), 'Network diagnostic leaked the key.');
    echo "OK: network failures are distinguished from provider refusals\n";

    prepareResponses([['status' => 429, 'body' => json_encode([
        'error' => ['code' => 'quota_exceeded', 'message' => "Quota reached\x1b mstrl_other_diagnostic_key sk-or-v1-other_diagnostic_key"],
    ], JSON_THROW_ON_ERROR)]]);
    $report = $diagnostics->completion();
    ensure($report['details']['error.code'] === 'quota_exceeded', 'Nested provider reason is missing.');
    ensure(!str_contains(json_encode($report), 'mstrl_other_diagnostic_key')
        && !str_contains(json_encode($report), 'sk-or-v1-other_diagnostic_key')
        && !str_contains($report['details']['error.message'], "\x1b"), 'Diagnostic did not sanitize other keys or terminal controls.');
    echo "OK: nested errors, other keys and terminal control characters are sanitized\n";
}
