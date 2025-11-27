<?php

namespace App\Shared\Application\Event;

interface EventHandlerRegistrarInterface
{
    /**
     * Register event handlers for the module
     *
     * @return void
     */
    public static function register(): void;
}