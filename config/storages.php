<?php

use Inquisition\Core\Infrastructure\Storage\LocalStorage;
use Inquisition\Core\Infrastructure\Storage\LocalStorageOptions;
use Inquisition\Foundation\Config\Config;

$config = Config::getInstance();

$config->merge([
    'storage' => [
        'default' => 'local',
        'providers' => [
            'local' => [
                'root_path' => 'storage',
                'options' => new LocalStorageOptions(),
                'provider' => LocalStorage::class,
            ]
        ]
    ]
]);
