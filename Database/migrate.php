<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use App\Config\Config;
use App\Database\Db;
use App\Logger\Logger;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/../');
$dotenv->load();

$config = new Config;
new Db($config);

$logger = new Logger($config);

$migrations = glob(__DIR__.'/migrations/*.php');

foreach ($migrations as $migrationPath) {
    $migration = require $migrationPath;
    try {
        $migration->up();
    } catch (PDOException $e) {
        if ($e->getCode() === '42S01') {
            $logger->log('info', 'MIGRATION MESSAGE: Migration '.basename($migrationPath, '.php').' has been already executed.');
        } else {
            $logger->log('debug', 'MIGRATION MESSAGE Error:'.$e->getMessage());
        }
    }
}
