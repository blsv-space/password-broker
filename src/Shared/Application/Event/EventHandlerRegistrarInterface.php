<?php

declare(strict_types=1);

namespace App\Shared\Application\Event;

interface EventHandlerRegistrarInterface
{
    /**
     * Register event handlers for the module
     *
     */
    public static function register(): void;
}
