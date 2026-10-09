<?php

declare(strict_types=1);

namespace App\Config;

class Config
{
    public function config(string $config): array|string|int
    {
        [$cfg, $key] = explode('.', $config);
        $configs = require __DIR__ . "/$cfg.php";
        return $configs[$key];
    }
}