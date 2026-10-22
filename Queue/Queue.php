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
    private const int MAX_ATTEMPTS = 3;

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
                $this->logger->info(
                    'QUEUE MESSAGE (PROCESS): process with PID: {pid} has been started.',
                    ['pid' => getmypid()],
                );

                if ($indexLastProcess !== $i) {

                    while ($newRedisClient->llen('queue') > 0 || $newRedisClient->llen('queue:processing') > 0) {

                        if (!($link = $newRedisClient->blmove('queue', 'queue:processing', 'RIGHT', 'LEFT', 30))) {
                            continue;
                        }

                        try {
                            $this->logger->info(
                                'QUEUE MESSAGE (PARSING): process {pid} started parsing link: {link}.',
                                ['pid' => getmypid(), 'link' => $link],
                            );
                            $parsedData = $this->handler->parse($link);

                            if (json_decode($parsedData) === []) {
                                $newRedisClient->lrem('queue:processing', 1, $link);
                                $newRedisClient->srem('set:processing', $link);

                            } else {
                                $newRedisClient->lpush('queue:parsed:data', [$parsedData]);
                            }

                            $newRedisClient->sadd('set:parsed:links', [$link]);
                            $this->logger->info(
                                'QUEUE MESSAGE (PARSING): process {pid} ended parsing link: {link}.',
                                ['pid' => getmypid(), 'link' => $link],
                            );

                        } catch (Throwable $e) {
                            $attempt = $newRedisClient->hincrby('queue:attempts', $link, 1);

                            if ($attempt >= self::MAX_ATTEMPTS) {
                                $newRedisClient->transaction(function ($transaction) use ($link) {
                                    $transaction->sadd('set:failed:links', [$link]);
                                    $transaction->lrem('queue:processing', 1, $link);
                                    $transaction->srem('set:processing', [$link]);
                                    $transaction->hdel('queue:attempts', [$link]);
                                });
                                $this->logger->error(
                                    'QUEUE MESSAGE (PARSING): Error: link {link} haven\'t been parsed after {attempts} attempts.',
                                    ['link' => $link, 'attempts' => self::MAX_ATTEMPTS],
                                );

                            } else {
                                $newRedisClient->lpush('queue', [$link]);
                                $newRedisClient->lrem('queue:processing', 1, $link);

                                $this->logger->error(
                                    'QUEUE MESSAGE (PARSING): Error: {error}.',
                                    ['error' => $e->getMessage()],
                                );
                            }
                        }
                    }
                } else {
                    $controller = new ParsedDataController();
                    new Db($this->config);

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
                            $newRedisClient->srem('set:processing', $link);
                        } catch (Throwable $e) {
                            $newRedisClient->lpush('queue:parsed:data', [$data[1]]);
                            $this->logger->error(
                                'QUEUE MESSAGE (SAVING): Error: {error}.',
                                ['error' => $e->getMessage()],
                            );
                        }
                    }
                }

                exit;
            }
        }

        while (($pid = pcntl_wait($status)) > 0) {
            $this->logger->info(
                'QUEUE MESSAGE (PROCESS): process with PID {pid} has done.',
                ['pid' => $pid],
            );
        }
    }

    private function moveToMainQueue(): void
    {
        $this->logger->info('QUEUE MESSAGE (MOVING LINKS): start');
        $count = $this->redisClient->connection()->llen('queue:processing');

        for ($i = 0; $i < $count; $i++) {
            $this->redisClient->connection()->lmove(
                'queue:processing',
                'queue',
                'RIGHT',
                'LEFT',
            );
        }
        $this->logger->info('QUEUE MESSAGE (MOVING LINKS): {count} links have been moved.', ['count' => $count]);
    }

    private function getNumberProcess(): int
    {
        if ($numberProcesses = (int)$this->config->config('queue.number_processes')) {
            return $numberProcesses + self::SAVING_PROCESS;
        }

        return self::DEFAULT_COUNT_PROCESSES;
    }
}
