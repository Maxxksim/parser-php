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

        try {
            $response = $this->client->request($method, $url, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',
                    'Accept' => '*/*'
                ],
                'proxy' => $this->getProxy(),
                'timeout' => 30,
            ]);
        } catch (GuzzleException $e) {
            echo "Request error: {$e->getMessage()}";
        }

        return $response->getBody();
    }

    private function getProxy(): string
    {
        if ((bool)$this->config->config('request.with_proxy') === false) {
            return '';
        }

        if (static::$indexNextProxy + 1 > count($this->proxies)) {
            static::$indexNextProxy = 0;
        }

        $proxy = $this->proxies[static::$indexNextProxy];
        static::$indexNextProxy += 1;


        return $this->makeProxyUrl($proxy);
    }

    private function makeProxyUrl(string $proxy): string
    {
        return 'http://' .
            rawurlencode($this->config->config('request.proxy.username')) .
            ':' .
            rawurlencode($this->config->config('request.proxy.password')) .
            '@' . $proxy;
    }
}