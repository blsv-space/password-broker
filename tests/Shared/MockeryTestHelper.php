<?php

declare(strict_types=1);

namespace Tests\Shared;

use Tests\Shared\Mock\ReplicatorMock;

final readonly class MockeryTestHelper
{
    private function __construct() {}

    public static function init(): void
    {
        ReplicatorMock::getInstance();
    }
}
