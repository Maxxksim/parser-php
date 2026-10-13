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

    public function makeRequest(string $method, string $url): ?string
    {
        $withProxy = (bool)$this->config->config('request.with_proxy');
        try {
            $response = $this->client->request($method, $url, [
                'proxy' => $withProxy ? $this->getProxy() : '',
                'timeout' => 30
            ]);
        } catch (GuzzleException $e) {
            echo "Request error: {$e->getMessage()}";
            exit(0);
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