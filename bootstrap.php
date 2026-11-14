<?php

declare(strict_types=1);

$root = __DIR__;

require $root . '/vendor/autoload.php';

date_default_timezone_set((string) config('app.timezone', 'UTC'));
mb_internal_encoding('UTF-8');

if (PHP_SAPI !== 'cli') {
    ini_set('display_errors', config('app.debug') ? '1' : '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
}

set_error_handler(static function (int $severity, string $message, string $file = '', int $line = 0): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(static function (Throwable $e): void {
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, get_class($e) . ': ' . $e->getMessage() . PHP_EOL . $e->getFile() . ':' . $e->getLine() . PHP_EOL);
        exit(1);
    }
    if ($e instanceof ChallengeChain\Core\HttpException) {
        render_error($e->getStatus(), $e->getMessage());
        return;
    }
    log_message('error', get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        render_error(500, 'Something went wrong on our side. Please try again.');
        return;
    }
    echo 'Internal server error';
});

register_shutdown_function(static function (): void {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        log_message('error', 'Fatal: ' . $error['message'] . ' @ ' . $error['file'] . ':' . $error['line']);
    }
});
