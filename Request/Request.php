<?php

declare(strict_types=1);

namespace App\Request;

use App\Config\Config;
use App\Logger\Logger;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

class Request
{
    private Client $client;

    private array $proxies;

    private static int $indexNextProxy = 0;

    public function __construct(private Config $config, private Logger $logger)
    {
        $this->client = new Client;
        $this->proxies = $this->config->config('request.proxies');
    }

    public function makeRequest(string $method, string $url): ResponseInterface
    {

        try {
            $response = $this->client->request($method, $url, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36',
                    'Accept' => '*/*',
                ],
                'proxy' => $this->getProxy(),
                'timeout' => 30,
            ]);
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                $response = $e->getResponse();
            } else {

                throw $e;
            }
        } catch (GuzzleException $e) {
            $this->logger->error("REQUEST MESSAGE: Error: {$e->getMessage()}");

            throw $e;
        }

        return $response;
    }

    private function getProxy(): string
    {
        if (filter_var($this->config->config('request.with_proxy'), FILTER_VALIDATE_BOOLEAN) === false) {
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
