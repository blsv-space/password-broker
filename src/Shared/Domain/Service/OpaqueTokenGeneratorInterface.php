<?php

declare(strict_types=1);

namespace App\Shared\Domain\Service;

interface OpaqueTokenGeneratorInterface
{
    public function generate(): string;
}
