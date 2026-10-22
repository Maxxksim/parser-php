<?php

declare(strict_types=1);

return [
    'proxies' => [
        'disp.oxylabs.io:8001',
        'disp.oxylabs.io:8002',
        'disp.oxylabs.io:8003',
        'disp.oxylabs.io:8004',
        'disp.oxylabs.io:8005',
        'disp.oxylabs.io:8006',
        'disp.oxylabs.io:8007',
        'disp.oxylabs.io:8008',
        'disp.oxylabs.io:8009',
        'disp.oxylabs.io:8010',
    ],
    'with_proxy' => $_ENV['WITH_PROXY'],
    'proxy' => [
        'username' => $_ENV['PROXY_USERNAME'],
        'password' => $_ENV['PROXY_PASSWORD'],
    ],
];
