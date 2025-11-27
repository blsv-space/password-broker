<?php

namespace App\Shared\Domain\Service;

interface OpaqueTokenGeneratorInterface
{
    public function generate(): string;
}