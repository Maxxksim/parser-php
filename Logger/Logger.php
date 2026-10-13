<?php

declare(strict_types=1);

namespace App\Logger;

use App\Config\Config;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger as MLogger;

class Logger
{
    private const array LOG_MODE_MAPPING = [
        'debug' => 'php://stdout',
        'file' => __DIR__.'/../logs.log',
    ];

    private MLogger $logger;

    public function __construct(private Config $config)
    {
        $this->logger = new MLogger('parser');
        $handler = new StreamHandler($this->getLogMode(), Level::Warning);
        $handler->setFormatter(new LineFormatter);
        $this->logger->pushHandler($handler);

    }

    private function getLogMode(): string
    {
        return self::LOG_MODE_MAPPING[$this->config->config('log.mode')];
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }
}
