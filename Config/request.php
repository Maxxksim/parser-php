<?php

declare(strict_types=1);

return [
    'proxies' => [
        'isp.oxylabs.io:8001',
        'isp.oxylabs.io:8002',
        'isp.oxylabs.io:8003',
        'isp.oxylabs.io:8004',
        'isp.oxylabs.io:8005',
        'isp.oxylabs.io:8006',
        'isp.oxylabs.io:8007',
        'isp.oxylabs.io:8008',
        'isp.oxylabs.io:8009',
        'isp.oxylabs.io:8010',
    ],
    'with_proxy' => $_ENV['WITH_PROXY'],
    'proxy' => [
        'username' => $_ENV['PROXY_USERNAME'],
        'password' => $_ENV['PROXY_PASSWORD'],
    ],
];
