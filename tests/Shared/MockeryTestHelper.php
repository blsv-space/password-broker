<?php

namespace Tests\Shared;

use Inquisition\Foundation\Singleton\SingletonRegistry;
use Tests\Shared\Mock\ReplicatorMock;

readonly final class MockeryTestHelper
{
    private function __construct()
    {
    }

    /**
     * @return void
     */
    public static function init(): void
    {
        SingletonRegistry::reset();
        ReplicatorMock::getInstance();
    }
}