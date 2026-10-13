<?php

declare(strict_types=1);

namespace App\Logger;

use App\Config\Config;
use InvalidArgumentException;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger as MLogger;

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
        $handler->setFormatter(new LineFormatter(null, null, false, true));
        $this->logger->pushHandler($handler);
    }

    private function getLogMode(): string
    {
        try {
            $mode = self::LOG_MODE_MAPPING[$this->config->config('log.mode')];
        } catch (InvalidArgumentException) {
            $mode = 'file';
        }

        return $mode;
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }
}
