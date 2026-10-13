<?php

namespace App\Queue;

use App\Config\Config;
use App\Database\Db;
use App\Parser\Parser;
use App\Redis\Redis;

class Queue
{
    private int $countProcesses;

    public function __construct(private Redis $redisClient, private Config $config, private Parser $handler)
    {
        $this->countProcesses = $this->config->config('queue.number_processes') ?? 1;
    }

    public function startQueue(): void
    {
        $this->moveToMainQueue();

        $this->redisClient->connection()->disconnect();

        for ($i = 0; $i < $this->countProcesses; $i++) {

            $pid = pcntl_fork();

            if ($pid === 0) {

                $newRedisClient = $this->redisClient->getInstance();
                $newDb = new Db($this->config);

                echo "PROCESS WITH PID:" . getmypid() . " is working now..." . PHP_EOL;

                while ($newRedisClient->llen('queue') > 0 || $newRedisClient->llen('queue:processing') > 0) {

                    if (!($link = $this->redisClient->connection()->blmove('queue', 'queue:processing', 'RIGHT', 'LEFT', 30))) {
                        continue;
                    }

                    try {
                        if ($newRedisClient->sismember('queue:parsed', $link)) {
                            $this->handler->parse($link);
                            $newRedisClient->sadd('queue:parsed', [$link]);
                        }
                    } catch (\Throwable $e) {
                        $newRedisClient->lpush('queue', [$link]);
                    } finally {
                        $newRedisClient->lrem('queue:processing', 1, $link);
                    }
                }

                exit(0);
            }
        }

        while (($pid = pcntl_wait($status)) > 0) {
            echo "PROCESS WITH PID:" . $pid . " has done." . PHP_EOL;
        }
    }

    private function moveToMainQueue(): void
    {
        $count = $this->redisClient->connection()->llen('queue:processing');
        for ($i = 0; $i < $count; $i++) {
            $this->redisClient->connection()->lmove('queue:processing', 'queue', 'RIGHT', 'LEFT');
        }
    }
}