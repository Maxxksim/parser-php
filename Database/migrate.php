<?php

declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";

use App\Database\Db;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

new Db();

$migrations = glob(__DIR__ . '/migrations/*.php');

foreach ($migrations as $migrationPath) {
    $migration = require $migrationPath;
    try {
        $migration->up();
    } catch (PDOException $e) {
        if ($e->getCode() === '42S01') {
            echo 'Migration ' . basename($migrationPath, '.php') . ' has been already executed.' . PHP_EOL;
        } else {
            echo 'Error:' . $e->getMessage() . PHP_EOL;
        }
    }
}
