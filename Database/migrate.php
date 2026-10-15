<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Config;
use App\Database\Db;
use App\Logger\Logger;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$config = new Config;
new Db($config);

$logger = new Logger($config);

$migrations = glob(__DIR__ . '/migrations/*.php');

foreach ($migrations as $migrationPath) {
    $migration = require $migrationPath;

    try {
        $migration->up();
        $logger->info(
            'MIGRATION MESSAGE: Migration {migration} has been migrated.',
            ['migration' => basename($migrationPath, '.php')],
        );
    } catch (PDOException $e) {

        if ($e->getCode() === '42S01') {
            $logger->info(
                'MIGRATION MESSAGE: Migration {migration} has been already executed.',
                ['migration' => basename($migrationPath, '.php')],
            );
        } else {
            $logger->error('MIGRATION MESSAGE: Error: {error}.', ['error' => $e->getMessage()]);
        }
    }
}
