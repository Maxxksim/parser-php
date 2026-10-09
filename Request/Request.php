<?php

namespace App\Request;

use App\Config\Config;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class Request
{
    private Client $client;
    private array $proxies;
    private static int $indexNextProxy = 0;

    public function __construct(private Config $config)
    {
        $this->client = new Client();
        $this->proxies = $this->config->config('request.proxies');
    }

    public function makeRequest(string $method, string $url, bool $withProxy = false): ?string
    {
        try {
            $response = $this->client->request($method, $url, [
                'headers' => [
                    'proxy' => $withProxy ? $this->getProxy() : '',
                    'User-Agent' => 'Mozilla/5.0 (Linux; Android 16; Pixel 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36'
                ]
            ]);
        } catch (GuzzleException $e) {
            echo "Request error: {$e->getMessage()}";
            return null;
        }

        return $response->getBody();
    }

    private function getProxy(): string
    {
        if (static::$indexNextProxy + 1 > count($this->proxies)) {
            static::$indexNextProxy = 0;
        }

        $proxy = $this->proxies[static::$indexNextProxy];
        static::$indexNextProxy += 1;

        return $proxy;
    }
}