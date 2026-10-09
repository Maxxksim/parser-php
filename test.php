<?php

declare(strict_types=1);

use App\Database\Db;
use App\Parser\Parser;
use App\Request\Request;
use App\Config\Config;

require_once __DIR__ . "/vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

new Db();
$config = new Config();
$request = new Request($config);
$parser = new Parser($request);
$parser->parse();









