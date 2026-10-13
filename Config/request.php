<?php

return [
    'proxies' => [
        "http://{$_ENV['PROXY_USERNAME']}:{$_ENV['PROXY_PASSWORD']}@45.38.107.97:6014",
        "http://{$_ENV['PROXY_USERNAME']}:{$_ENV['PROXY_PASSWORD']}@64.137.96.74:6641",
        "http://{$_ENV['PROXY_USERNAME']}:{$_ENV['PROXY_PASSWORD']}@198.23.243.226:6361",
        "http://{$_ENV['PROXY_USERNAME']}:{$_ENV['PROXY_PASSWORD']}@198.23.243.226:6370",
        "http://{$_ENV['PROXY_USERNAME']}:{$_ENV['PROXY_PASSWORD']}@84.247.60.125:6095",
        "http://{$_ENV['PROXY_USERNAME']}:{$_ENV['PROXY_PASSWORD']}@191.96.254.138:6185",
        "http://{$_ENV['PROXY_USERNAME']}:{$_ENV['PROXY_PASSWORD']}@198.46.161.42:5092",
        "http://{$_ENV['PROXY_USERNAME']}:{$_ENV['PROXY_PASSWORD']}@64.137.10.153:5803",
        "http://{$_ENV['PROXY_USERNAME']}:{$_ENV['PROXY_PASSWORD']}@104.164.49.38:7693",
        "http://{$_ENV['PROXY_USERNAME']}:{$_ENV['PROXY_PASSWORD']}@23.26.71.145:5628",
    ],
    'with_proxy' => $_ENV['WITH_PROXY']
];