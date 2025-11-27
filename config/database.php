<?php

use Inquisition\Core\Infrastructure\Persistence\DbDriverEnum;
use Inquisition\Foundation\Config\Config;

$config = Config::getInstance();

$config->merge([
    'database' => [
        'default' => 'default',
        'migration' => [
            'paths' => [
                'Identity' => 'src/Module/Identity/Infrastructure/Database/Migrations',
            ],
        ],
        'connections' => [
            'default' => [
                'driver' => DbDriverEnum::MYSQL->value,
                'database' => 'inquisition',
                'host' => 'localhost',
//                'unix_socket' => '/var/run/mysqld/mysqld.sock',
                'username' => 'root',
                'password' => '',
                'port' => 3306,
                'charset' => 'utf8mb4',
                'options' => [],
            ]
        ]
    ]
]);