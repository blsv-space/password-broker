<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

interface EnumToArrayInterface
{
    public static function toArray(): array;
}
