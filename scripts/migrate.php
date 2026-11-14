<?php

declare(strict_types=1);

/**
 * ChallengeChain migration runner — php scripts/migrate.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require dirname(__DIR__) . '/bootstrap.php';

use ChallengeChain\Core\Database;

$pdo = Database::pdo();

$pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    migration TEXT NOT NULL UNIQUE,
    ran_at TEXT NOT NULL DEFAULT (datetime('now'))
)");

$applied = array_column(Database::fetchAll('SELECT migration FROM schema_migrations'), 'migration');
$files = glob(dirname(__DIR__) . '/database/migrations/*.php') ?: [];
sort($files);

$ran = 0;
foreach ($files as $file) {
    $name = basename($file, '.php');
    if (in_array($name, $applied, true)) {
        continue;
    }
    $sql = require $file;
    if (!is_string($sql) || trim($sql) === '') {
        fwrite(STDERR, "Migration {$name} did not return SQL.\n");
        exit(1);
    }
    try {
        Database::transaction(static function () use ($pdo, $sql, $name): void {
            $pdo->exec($sql);
            $stmt = $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (:m)');
            $stmt->execute(['m' => $name]);
        });
    } catch (Throwable $e) {
        fwrite(STDERR, "Migration {$name} failed: " . $e->getMessage() . "\n");
        exit(1);
    }
    echo "Applied: {$name}\n";
    $ran++;
}

echo $ran === 0 ? "Database is up to date.\n" : "Done — {$ran} migration(s) applied.\n";
