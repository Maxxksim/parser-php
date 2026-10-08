<?php

declare(strict_types=1);

namespace App\Redis;

use Predis\Client as PredisClient;

class Redis
{
    private PredisClient $connection;

    public function __construct()
    {
        $this->connection = new PredisClient([
            'scheme' => 'tcp',
            'host' => '127.0.0.1',
            'port' => 6379,
            'password' => $_ENV['REDIS_PASSWORD'],
            'database' => 0,
        ]);
    }

    public function connection(): PredisClient
    {
        return $this->connection;
    }
}