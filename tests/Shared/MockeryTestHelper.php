<?php

declare(strict_types=1);

namespace Tests\Shared;

use Inquisition\Foundation\Singleton\SingletonRegistry;
use Tests\Shared\Mock\ReplicatorMock;

final readonly class MockeryTestHelper
{
    private function __construct() {}

    public static function init(): void
    {
        SingletonRegistry::reset();
        ReplicatorMock::getInstance();
    }
}
