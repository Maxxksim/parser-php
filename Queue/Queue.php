<?php

declare(strict_types=1);

namespace App\Queue;

use App\Config\Config;
use App\Database\Db;
use App\Logger\Logger;
use App\Parser\Parser;
use App\Redis\Redis;

class Queue
{
    private int $countProcesses;

    public function __construct(private Redis $redisClient, private Config $config, private Parser $handler, private Logger $logger)
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

                $this->logger->log('info', 'QUEUE MESSAGE (PROCESS): process with PID: '.getmypid().' has been started.');

                while ($newRedisClient->llen('queue') > 0 || $newRedisClient->llen('queue:processing') > 0) {

                    if (! ($link = $newRedisClient->blmove('queue', 'queue:processing', 'RIGHT', 'LEFT', 30))) {
                        continue;
                    }

                    try {
                        if (! $newRedisClient->sismember('queue:parsed', $link)) {
                            $this->logger->log('info', 'QUEUE MESSAGE (PARSING): process '.getmypid().' started parsing link: '.$link);
                            $this->handler->parse($link);
                            $newRedisClient->sadd('queue:parsed', [$link]);
                            $this->logger->log('info', 'QUEUE MESSAGE (PARSING): process '.getmypid().' ended parsing link: '.$link);
                        }
                    } catch (\Throwable $e) {
                        $newRedisClient->lpush('queue', [$link]);
                        $this->logger->log('debug', 'QUEUE MESSAGE (PARSING): error: '.$e->getMessage());
                    } finally {
                        $newRedisClient->lrem('queue:processing', 1, $link);
                    }
                }

                exit(0);
            }
        }

        while (($pid = pcntl_wait($status)) > 0) {
            $this->logger->log('info', 'QUEUE MESSAGE (PROCESS): process with PID '.$pid.'has done.');
        }
    }

    private function moveToMainQueue(): void
    {
        $this->logger->log('info', 'QUEUE MESSAGE (MOVING LINKS): start');
        $count = $this->redisClient->connection()->llen('queue:processing');
        for ($i = 0; $i < $count; $i++) {
            $this->redisClient->connection()->lmove('queue:processing', 'queue', 'RIGHT', 'LEFT');
        }
        $this->logger->log('info', "QUEUE MESSAGE (MOVING LINKS): $count links have been moved.");
    }
}
