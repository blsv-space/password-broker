<?php

use Inquisition\Foundation\Config\Config;

$config = Config::getInstance();

$config->merge([
    'kafka' => [
        'brokers' => 'PLAINTEXT://kafka:9092'
    ]
]);