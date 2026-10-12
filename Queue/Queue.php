<?php

namespace App\Queue;

use App\Config\Config;
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
        for ($i = 0; $i < $this->countProcesses; $i++) {

            $pid = pcntl_fork();

            if ($pid === 0) {

                echo "PROCESS WITH PID:" . getmypid() . "are working now..." . PHP_EOL;

                while ($this->redisClient->connection()->llen('queue') > 0 || $this->redisClient->connection()->llen('queue:processing') > 0) {

                    if (!($link = $this->redisClient->connection()->blmove('queue', 'queue:processing', 'RIGHT', 'LEFT', 30))) {
                        continue;
                    }

                    try {
                        if (!$this->redisClient->connection()->sismember('queue:parsed', $link)) {
                            $this->handler->parse($link);
                            $this->redisClient->connection()->sadd('queue:parsed', [$link]);
                        }
                    } catch (\Throwable $e) {
                        $this->redisClient->connection()->lpush('queue', [$link]);
                    } finally {
                        $this->redisClient->connection()->lrem('queue:processing', 1, $link);
                    }
                }

                exit(0);
            }
        }

        while (pcntl_wait($status) > 0) {
            echo "PROCESS WITH PID:" . getmypid() . " has done." . PHP_EOL;
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