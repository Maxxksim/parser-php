<?php

declare(strict_types=1);

namespace App\Queue;

use App\Config\Config;
use App\Controller\ParsedDataController;
use App\Database\Db;
use App\Logger\Logger;
use App\Parser\Parser;
use App\Redis\Redis;
use Throwable;

class Queue
{
    private int $countProcesses;

    private const int DEFAULT_COUNT_PROCESSES = 2;
    private const int SAVING_PROCESS = 1;

    public function __construct(private Redis $redisClient, private Config $config, private Parser $handler, private Logger $logger)
    {
        $this->countProcesses = $this->getNumberProcess();
    }

    public function startQueue(): void
    {
        $this->moveToMainQueue();
        $this->redisClient->connection()->disconnect();
        $indexLastProcess = $this->countProcesses - self::SAVING_PROCESS;

        for ($i = 0; $i < $this->countProcesses; $i++) {
            $pid = pcntl_fork();

            if ($pid === 0) {

                $newRedisClient = $this->redisClient->getInstance();
                new Db($this->config);

                $this->logger->info('QUEUE MESSAGE (PROCESS): process with PID: ' . getmypid() . ' has been started.');

                if ($indexLastProcess !== $i) {

                    while ($newRedisClient->llen('queue') > 0 || $newRedisClient->llen('queue:processing') > 0) {
                        if (!($link = $newRedisClient->blmove('queue', 'queue:processing', 'RIGHT', 'LEFT', 30))) {
                            continue;
                        }

                        try {

                            if (!$newRedisClient->sismember('queue:parsed:links', $link)) {
                                $this->logger->info('QUEUE MESSAGE (PARSING): process ' . getmypid() . ' started parsing link: ' . $link);

                                if (($parsedData = $this->handler->parse($link))) {
                                    $newRedisClient->lpush('queue:parsed:data', [$parsedData]);
                                }

                                $newRedisClient->sadd('queue:parsed:links', [$link]);
                                $this->logger->info('QUEUE MESSAGE (PARSING): process ' . getmypid() . ' ended parsing link: ' . $link);
                            }
                        } catch (Throwable $e) {
                            $newRedisClient->lrem('queue:processing', 1, $link);
                            $newRedisClient->lpush('queue', [$link]);
                            $this->logger->error('QUEUE MESSAGE (PARSING): error: ' . $e->getMessage());
                        }
                    }
                } else {
                    $controller = new ParsedDataController();

                    while (
                        $newRedisClient->llen('queue') > 0 ||
                        $newRedisClient->llen('queue:processing') > 0 ||
                        $newRedisClient->llen('queue:parsed:data') > 0
                    ) {

                        if (!($data = $newRedisClient->brpop('queue:parsed:data', 30))) {
                            continue;
                        }

                        try {
                            $link = $controller->save($data[1]);
                            $newRedisClient->lrem('queue:processing', 1, $link);
                        } catch (Throwable $e) {
                            $newRedisClient->lpush('queue:parsed:data', [$data[1]]);
                            $this->logger->error('QUEUE MESSAGE (SAVING): error: ' . $e->getMessage());
                        }
                    }
                }

                exit;
            }
        }

        while (($pid = pcntl_wait($status)) > 0) {
            $this->logger->info('QUEUE MESSAGE (PROCESS): process with PID ' . $pid . ' has done.');
        }
    }

    private function moveToMainQueue(): void
    {
        $this->logger->info('QUEUE MESSAGE (MOVING LINKS): start');
        $count = $this->redisClient->connection()->llen('queue:processing');

        for ($i = 0; $i < $count; $i++) {
            $this->redisClient->connection()->lmove('queue:processing', 'queue', 'RIGHT', 'LEFT');
        }
        $this->logger->info("QUEUE MESSAGE (MOVING LINKS): $count links have been moved.");
    }

    private function getNumberProcess(): int
    {
        if ($numberProcesses = (int)$this->config->config('queue.number_processes')) {
            return $numberProcesses + self::SAVING_PROCESS;
        }

        return self::DEFAULT_COUNT_PROCESSES;
    }
}
