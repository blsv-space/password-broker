<?php

declare(strict_types=1);

use Inquisition\Foundation\Config\Config;

$config = Config::getInstance();

$config->merge([
    'kafka' => [
        'brokers' => 'PLAINTEXT://kafka:9092',
    ],
]);
