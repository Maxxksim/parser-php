<?php

declare(strict_types=1);

namespace App\Config;

class Config
{
    public function config(string $config, mixed $default = null): mixed
    {
        [$configFile, $path] = explode('.', $config, 2);

        if (!$path) {
            return $configFile;
        }

        $settings = require __DIR__ . "/$configFile.php";

        foreach (explode('.', $path) as $section) {
            if (!array_key_exists($section, $settings) || !is_array($settings)) {
                return $default;
            }
            $settings = $settings[$section];
        }

        return $settings;
    }
}