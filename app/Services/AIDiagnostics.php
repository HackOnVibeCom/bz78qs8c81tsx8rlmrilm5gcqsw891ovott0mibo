<?php

declare(strict_types=1);

namespace ChallengeChain\Services;

/** Small, explicit account probes for the CLI; no quiz generation or retries. */
final class AIDiagnostics
{
    public function models(): array
    {
        return $this->request('/models');
    }

    public function completion(): array
    {
        return $this->request('/chat/completions', [
            'model' => (string) config('ai.model'),
            'messages' => [['role' => 'user', 'content' => 'Reply only OK.']],
            'max_tokens' => 8,
            'temperature' => 0,
        ]);
    }

    private function request(string $path, ?array $payload = null): array
    {
        $apiKey = (string) config('ai.api_key');
        $headers = [];
        $ch = curl_init((string) config('ai.base_url') . $path);
        if ($ch === false) {
            return ['status' => 0, 'network_error' => 'Initialisation cURL impossible.'];
        }
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json', 'Authorization: Bearer ' . $apiKey],
            CURLOPT_HEADERFUNCTION => function ($handle, string $header) use (&$headers, $apiKey): int {
                if (preg_match('/^(retry-after|x-request-id|x-ratelimit-[a-z0-9-]+):\s*(.+)$/i', trim($header), $matches)) {
                    $headers[strtolower($matches[1])] = self::redact($matches[2], $apiKey);
                }
                return strlen($header);
            },
        ];
        if ($payload !== null) {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_THROW_ON_ERROR);
        }
        curl_setopt_array($ch, $options);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $errno = curl_errno($ch);
        $networkError = curl_error($ch);
        curl_close($ch);
        $report = ['status' => $status, 'headers' => $headers];
        if ($body === false || $errno !== 0) {
            $report['network_error'] = self::redact($networkError, $apiKey);
            return $report;
        }
        $decoded = json_decode($body, true);
        if ($status >= 200 && $status < 300) {
            if (is_array($decoded['data'] ?? null)) {
                $report['model_count'] = count($decoded['data']);
            }
            return $report;
        }
        // Only selected error fields are shown, never the raw response or key.
        if (is_array($decoded)) {
            foreach (['root' => $decoded, 'error' => $decoded['error'] ?? null, 'detail' => $decoded['detail'] ?? null] as $prefix => $fields) {
                if (is_string($fields)) {
                    $report['details'][$prefix] = self::redact($fields, $apiKey);
                } elseif (is_array($fields)) {
                    foreach (['code', 'type', 'message', 'detail'] as $field) {
                        if (is_string($fields[$field] ?? null) || is_int($fields[$field] ?? null)) {
                            $report['details'][$prefix . '.' . $field] = self::redact((string) $fields[$field], $apiKey);
                        }
                    }
                }
            }
        }
        return $report;
    }

    private static function redact(string $value, string $apiKey): string
    {
        if ($apiKey !== '') {
            $value = str_replace($apiKey, '[CLE MASQUEE]', $value);
        }
        $value = preg_replace('/\b(?:mstrl_|sk-)[A-Za-z0-9_-]+/', '[CLE MASQUEE]', $value) ?? '';
        $value = preg_replace('/[\x00-\x1F\x7F]/', ' ', $value) ?? '';
        return mb_substr($value, 0, 1000);
    }
}
