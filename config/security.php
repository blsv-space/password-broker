<?php

use App\Shared\Infrastructure\Security\JwtAlgoEnum;
use Inquisition\Foundation\Config\Config;

$config = Config::getInstance();

$config->merge([
    'security' => [
        'secret' => null,
        'password_min_length' => 6,
        'refresh_token' => [
            'time_to_live' => '30 days', //https://www.php.net/manual/en/dateinterval.format.php
            'secret' => null,
        ],
        'jwt' => [
            'time_to_live' => '1 day', //https://www.php.net/manual/en/dateinterval.format.php
            'secret' => null,
            'algo' => JwtAlgoEnum::HS256->value,
        ],
    ]
]);