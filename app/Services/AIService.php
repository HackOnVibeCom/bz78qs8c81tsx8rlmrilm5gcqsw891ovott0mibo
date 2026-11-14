<?php

declare(strict_types=1);

namespace ChallengeChain\Services;

use ChallengeChain\Core\HttpException;
use JsonException;

/**
 * OpenAI-compatible challenge generator, configured for OpenRouter free models.
 * Falls back to the demo question bank when no API key is configured.
 */
final class AIService
{
    public const MAX_QUESTIONS = 15;
    public const MIN_QUESTIONS = 3;

    /**
     * @param array{category?:string, topic?:string, difficulty?:string, question_count?:int|string, language?:string} $params
     * @return array<string, mixed>
     */
    public function generate(array $params): array
    {
        $topic = trim((string) ($params['topic'] ?? ''));
        $category = (string) ($params['category'] ?? 'general');
        $difficulty = (string) ($params['difficulty'] ?? 'medium');
        $language = trim((string) ($params['language'] ?? 'en'));
        $count = (int) ($params['question_count'] ?? 5);

        if ($topic === '') {
            throw new HttpException(422, 'Please provide a topic for your challenge.');
        }
        if (mb_strlen($topic) > 120) {
            throw new HttpException(422, 'Topic must be 120 characters or fewer.');
        }
        if (!array_key_exists($category, DemoQuestionBank::CATEGORIES)) {
            $category = 'general';
        }
        if (!in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
            $difficulty = 'medium';
        }
        $count = max(self::MIN_QUESTIONS, min(self::MAX_QUESTIONS, $count));
        if ($language === '' || strlen($language) > 10) {
            $language = 'en';
        }

        $apiKey = (string) config('ai.api_key', '');
        if ($apiKey === '') {
            $data = DemoQuestionBank::generate($topic, $category, $difficulty, $count, $language);
            $data['demo'] = true;
            return $data;
        }

        $deadline = microtime(true) + max(1, (int) config('ai.timeout', 45));
        $raw = $this->requestChat($apiKey, $topic, $category, $difficulty, $count, $language, false, $deadline);
        try {
            $content = $this->extractContent($raw);
            $decoded = self::validatePayload($content, $count);
        } catch (HttpException $e) {
            self::logInvalidResponse($raw, $count);
            // One stricter retry with more room for the actual quiz JSON.
            $raw = $this->requestChat($apiKey, $topic, $category, $difficulty, $count, $language, true, $deadline);
            try {
                $content = $this->extractContent($raw);
                $decoded = self::validatePayload($content, $count);
            } catch (HttpException $retryError) {
                self::logInvalidResponse($raw, $count);
                throw $retryError;
            }
        }

        $decoded['category'] = $category;
        $decoded['topic'] = $topic;
        $decoded['difficulty'] = $difficulty;
        $decoded['language'] = $language;
        $decoded['demo'] = false;
        return $decoded;
    }

    private function extractContent(string $raw): array
    {
        try {
            $envelope = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new HttpException(502, 'The AI service returned an unreadable response. Please try again.');
        }

        if (($envelope['choices'][0]['finish_reason'] ?? null) === 'length') {
            throw new HttpException(502, 'Le modele IA a atteint sa limite avant de terminer le quiz. Reessayez avec moins de questions.');
        }
        $content = $envelope['choices'][0]['message']['content'] ?? null;
        if (is_array($content) && !array_is_list($content)) {
            return $content;
        }
        if (is_array($content)) {
            $parts = [];
            foreach ($content as $chunk) {
                if (is_array($chunk) && ($chunk['type'] ?? '') === 'text' && is_string($chunk['text'] ?? null)) {
                    $parts[] = $chunk['text'];
                }
            }
            $content = implode('', $parts);
        }
        if (!is_string($content) || trim($content) === '') {
            throw new HttpException(502, 'Le modele IA gratuit n\'a pas renvoye de quiz exploitable. Reessayez dans un instant ou avec moins de questions.');
        }
        $content = trim($content);
        // Strip markdown fences if present.
        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*/i', '', $content) ?? $content;
            $content = preg_replace('/\s*```$/', '', $content) ?? $content;
        }
        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new HttpException(502, 'The AI returned invalid JSON. Please try again.');
        }
        if (!is_array($decoded)) {
            throw new HttpException(502, 'The AI returned an unexpected format. Please try again.');
        }
        return $decoded;
    }

    private function requestChat(string $apiKey, string $topic, string $category, string $difficulty, int $count, string $language, bool $strict, float $deadline): string
    {
        $system = 'You are ChallengeChain, an expert quiz author. '
            . 'Respond ONLY with valid JSON, no markdown, no commentary. '
            . 'Schema: {"title": string (max 90 chars), "description": string (max 300 chars), '
            . '"questions": [{"question": string, "options": array of exactly 4 distinct strings, '
            . '"correct_index": integer 0-3, "explanation": string}]}. '
            . "Create exactly {$count} multiple-choice questions. Exactly one correct option per question. "
            . 'Keep questions, options and explanations concise. '
            . 'Difficulty: ' . $difficulty . '. Language: ' . $language . ' (write questions in this language).';
        if ($strict) {
            $system .= ' Your previous reply failed validation. Output ONLY the JSON object matching the schema exactly.';
        }

        $user = 'Category: ' . $category . "\nTopic: " . $topic . "\nNumber of questions: " . $count;

        $outputBudget = max(3072, 1024 + $count * 512);
        $payload = [
            'model' => (string) config('ai.model', 'openrouter/free'),
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'temperature' => 0.8,
            'response_format' => ['type' => 'json_object'],
            'max_tokens' => min(16384, $outputBudget * ($strict ? 2 : 1)),
        ];
        if (config('ai.provider_name') === 'OpenRouter') {
            // Reasoning shares max_tokens with visible output. Keep space for
            // the quiz rather than merely hiding reasoning with exclude=true.
            $payload['reasoning'] = ['max_tokens' => 256];
        }

        $url = (string) config('ai.base_url') . '/chat/completions';
        $provider = (string) config('ai.provider_name', 'Le service IA');

        for ($attempt = 0; ; $attempt++) {
            if (microtime(true) >= $deadline) {
                throw new HttpException(502, 'La generation IA a depasse le delai disponible. Reessayez avec moins de questions.');
            }
            $retryAfter = null;
            $requestsPerMinute = null;
            $ch = curl_init($url);
            if ($ch === false) {
                throw new HttpException(502, 'Could not initialize the AI request.');
            }
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey,
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => max(1, (int) ceil($deadline - microtime(true))),
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_HEADERFUNCTION => static function ($handle, string $header) use (&$retryAfter, &$requestsPerMinute): int {
                    if (preg_match('/^x-ratelimit-limit-req-minute:\s*(\d+)$/i', trim($header), $matches)) {
                        $requestsPerMinute = (int) $matches[1];
                    }
                    if (preg_match('/^Retry-After:\s*(.+)$/i', trim($header), $matches)) {
                        $value = trim($matches[1]);
                        $date = ctype_digit($value) ? false : strtotime($value);
                        if (ctype_digit($value)) {
                            $retryAfter = (float) $value;
                        } elseif ($date !== false) {
                            $retryAfter = (float) max(0, $date - time());
                        }
                    }
                    return strlen($header);
                },
            ]);
            $body = curl_exec($ch);
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);

            if ($body === false || $errno !== 0) {
                log_message('error', 'AI cURL error: ' . $error);
                throw new HttpException(502, 'Could not reach the AI service. Please try again.');
            }
            if ($status >= 200 && $status < 300) {
                return (string) $body;
            }

            // Provider error bodies can echo credentials; keep them out of logs.
            log_message('error', 'AI HTTP ' . $status);
            if ($status === 429) {
                if ($requestsPerMinute === 0) {
                    throw new HttpException(429, $provider . ' autorise 0 requete de generation par minute pour ce modele avec ce compte. Verifiez les limites et l\'acces API au modele dans le compte du fournisseur.');
                }
                if (self::isExhaustedQuota((string) $body)) {
                    throw new HttpException(429, 'Le quota ou plafond du compte ' . $provider . ' est atteint (HTTP 429). Verifiez l\'utilisation et les limites du compte.');
                }
                $delay = $retryAfter ?? (2 ** $attempt + random_int(0, 250) / 1000);
                // At most two retries, inside the original request timeout.
                // Long Retry-After values are returned to the caller, never shortened.
                if ($attempt < 2 && $delay <= 10 && microtime(true) + $delay + 1 < $deadline) {
                    usleep((int) ($delay * 1_000_000));
                    continue;
                }
                $hint = $retryAfter !== null && $retryAfter > 0
                    ? ' Reessayez dans au moins ' . (int) ceil($retryAfter) . ' secondes.'
                    : ' Reessayez plus tard.';
                throw new HttpException(429, $provider . ' limite les requetes (HTTP 429).' . $hint . ' Si cela persiste, verifiez les quotas du compte.');
            }
            if ($status === 401 || $status === 403) {
                throw new HttpException(502, 'The AI credentials were rejected. Check AI_API_KEY.');
            }
            throw new HttpException(502, 'The AI service returned an error (HTTP ' . $status . '). Please try again.');
        }
    }

    private static function logInvalidResponse(string $raw, int $questionCount): void
    {
        $response = json_decode($raw, true);
        if (!is_array($response)) {
            log_message('warn', 'AI invalid output: unreadable envelope');
            return;
        }
        $safeId = static fn (mixed $value): string => is_string($value)
            ? substr(preg_replace('/[^a-zA-Z0-9_\/.:\-]/', '', $value) ?? '', 0, 160)
            : '';
        // No prompt, generated text, reasoning text or credentials are logged.
        log_message('warn', 'AI invalid output: ' . json_encode([
            'model' => $safeId($response['model'] ?? ''),
            'finish_reason' => $safeId($response['choices'][0]['finish_reason'] ?? ''),
            'completion_tokens' => (int) ($response['usage']['completion_tokens'] ?? 0),
            'reasoning_tokens' => (int) ($response['usage']['completion_tokens_details']['reasoning_tokens'] ?? 0),
            'requested_questions' => $questionCount,
        ], JSON_UNESCAPED_SLASHES));
    }

    private static function isExhaustedQuota(string $body): bool
    {
        $error = json_decode($body, true);
        if (!is_array($error)) {
            return false;
        }
        $details = [];
        foreach ([$error, $error['error'] ?? [], $error['detail'] ?? []] as $fields) {
            if (is_string($fields)) {
                $details[] = $fields;
            } elseif (is_array($fields)) {
                foreach (['message', 'type', 'code', 'detail'] as $field) {
                    if (is_string($fields[$field] ?? null)) {
                        $details[] = $fields[$field];
                    }
                }
            }
        }
        return (bool) preg_match('/insufficient_quota|quota_exceeded|quota (?:exceeded|exhausted)|monthly|daily|spending|billing|credit|budget/i', implode(' ', $details));
    }

    /**
     * Validates and normalises AI-generated challenge JSON before any DB insert.
     *
     * @return array{title:string, description:string, questions:list<array{question:string, options:list<string>, correct_index:int, explanation:string}>}
     */
    public static function validatePayload(mixed $data, ?int $expectedCount = null): array
    {
        if (!is_array($data)) {
            throw new HttpException(422, 'AI response must be a JSON object.');
        }

        $title = trim((string) ($data['title'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $questions = $data['questions'] ?? null;

        if ($title === '') {
            throw new HttpException(422, 'AI response is missing a challenge title.');
        }
        if (!is_array($questions) || $questions === []) {
            throw new HttpException(422, 'AI response contains no questions.');
        }
        if (count($questions) > self::MAX_QUESTIONS + 5) {
            throw new HttpException(422, 'AI returned too many questions.');
        }
        if ($expectedCount !== null && count($questions) !== $expectedCount) {
            throw new HttpException(422, 'Le modele IA doit renvoyer exactement ' . $expectedCount . ' questions.');
        }

        $clean = [];
        foreach (array_values($questions) as $i => $q) {
            if (!is_array($q)) {
                throw new HttpException(422, 'Question ' . ($i + 1) . ' is malformed.');
            }
            $text = self::cleanText((string) ($q['question'] ?? ''), 500);
            $options = $q['options'] ?? null;
            $correct = $q['correct_index'] ?? $q['correct'] ?? null;
            $explanation = self::cleanText((string) ($q['explanation'] ?? ''), 500);

            if ($text === '') {
                throw new HttpException(422, 'Question ' . ($i + 1) . ' has no text.');
            }
            if (!is_array($options) || count($options) < 2) {
                throw new HttpException(422, 'Question ' . ($i + 1) . ' needs at least 2 options.');
            }
            $options = array_values(array_filter(array_map(
                static fn ($o): string => self::cleanText((string) $o, 160),
                $options
            ), static fn (string $o): bool => $o !== ''
            ));
            if (count($options) < 2 || count($options) > 6) {
                throw new HttpException(422, 'Question ' . ($i + 1) . ' has an invalid number of options.');
            }
            if (!is_numeric($correct)) {
                throw new HttpException(422, 'Question ' . ($i + 1) . ' is missing a correct answer index.');
            }
            $correctIndex = (int) $correct;
            if ($correctIndex < 0 || $correctIndex >= count($options)) {
                throw new HttpException(422, 'Question ' . ($i + 1) . ' has an out-of-range correct answer.');
            }

            $clean[] = [
                'question' => $text,
                'options' => $options,
                'correct_index' => $correctIndex,
                'explanation' => $explanation,
            ];
        }

        return [
            'title' => mb_substr($title, 0, 120),
            'description' => mb_substr($description, 0, 500),
            'questions' => $clean,
        ];
    }

    private static function cleanText(string $value, int $max): string
    {
        $value = strip_tags($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        return mb_substr(trim($value), 0, $max);
    }
}
