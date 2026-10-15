<?php

declare(strict_types=1);

namespace App\Logger;

use App\Config\Config;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger as MLogger;
use Monolog\Processor\PsrLogMessageProcessor;

class Logger
{
    private const array LOG_MODE_MAPPING = [
        'debug' => 'php://stdout',
        'file' => __DIR__ . '/../Logs/logs.log',
    ];

    private MLogger $logger;

    public function __construct(private Config $config)
    {
        $this->logger = new MLogger('parser');
        $handler = new StreamHandler($this->getLogMode(), Level::Debug);
        $handler->setFormatter(
            new LineFormatter(
                null,
                null,
                false,
                true,
            ));
        $this->logger->pushHandler($handler);
        $this->logger->pushProcessor(new PsrLogMessageProcessor(null, true));
    }

    private function getLogMode(): string
    {
        return self::LOG_MODE_MAPPING[$this->config->config('log.mode')] ?? self::LOG_MODE_MAPPING['file'];
    }

    public function getMonolog(): MLogger
    {
        return $this->logger;
    }

    public function __call(string $level, array $arguments)
    {
        $this->logger->log($level, ...$arguments);
    }
}
