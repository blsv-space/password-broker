<?php

namespace Tests\Shared;

abstract class UnitTestCase extends AbstractTestCase
{
    protected function assertDomainEvent(string $expectedEvent, array $events): void
    {
        $this->assertContains($expectedEvent, array_map(fn($e) => get_class($e), $events));
    }
}