<?php

use Inquisition\Core\Application\Service\EnvironmentEnum;
use Inquisition\Foundation\Config\Config;

Config::getInstance()->merge([
    'app' => [
        'name' => 'Inquisition App',
        'mode' => EnvironmentEnum::PROD->value,
        'root_path' => dirname(__DIR__),],
]);