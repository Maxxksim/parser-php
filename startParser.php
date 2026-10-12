<?php

declare(strict_types=1);

use App\Database\Db;
use App\Parser\Parser;
use App\Queue\Queue;
use App\Redis\Redis;
use App\Request\Request;
use App\Config\Config;

require_once __DIR__ . "/vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();


$db = new Db();
$config = new Config();
$request = new Request($config);
$redis = new Redis();
$parser = new Parser($request, $redis);
$queue = new Queue($redis, $config, $parser);

$queue->startQueue();










