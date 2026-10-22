<?php

declare(strict_types=1);

namespace App\Redis;

use App\Config\Config;
use Predis\Client as PredisClient;

class Redis
{
    private PredisClient $client;

    /**
     * Creates the Redis client.
     *
     * @param Config $config
     */
    public function __construct(private Config $config)
    {
        $this->client = $this->getInstance();
    }

    /**
     * Returns the Redis client instance.
     *
     * @return PredisClient
     */
    public function connection(): PredisClient
    {
        return $this->client;
    }

    /**
     * Creates a new Predis client instance.
     *
     * @return PredisClient
     */
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
