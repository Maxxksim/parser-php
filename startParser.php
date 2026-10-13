<?php

declare(strict_types=1);

use App\Config\Config;
use App\Logger\Logger;
use App\Parser\Parser;
use App\Queue\Queue;
use App\Redis\Redis;
use App\Request\Request;
use Monolog\ErrorHandler;

require_once __DIR__.'/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$config = new Config;
$logger = new Logger($config);

ErrorHandler::register($logger->getMonolog());

$request = new Request($config, $logger);
$redis = new Redis($config);
$parser = new Parser($request, $redis, $logger);
$queue = new Queue($redis, $config, $parser, $logger);

$queue->startQueue();
