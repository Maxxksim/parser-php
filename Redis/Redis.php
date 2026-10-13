<?php

declare(strict_types=1);

namespace App\Redis;

use App\Config\Config;
use Predis\Client as PredisClient;
use Predis\Connection\ConnectionInterface;

class Redis
{
    private PredisClient $client;

    public function __construct(private Config $config)
    {
        $this->client = $this->getInstance();
    }

    public function connection(): PredisClient
    {
        return $this->client;
    }

    public function getInstance(): PredisClient
    {
        return new PredisClient([
            'scheme' => $this->config->config('database.redis.scheme'),
            'host' => $this->config->config('database.redis.host'),
            'port' => $this->config->config('database.redis.port'),
            'password' => $this->config->config('database.redis.password'),
            'database' => $this->config->config('database.redis.database'),
        ]);
    }
}