<?php

namespace App\Shared\Infrastructure\Security;

use App\Shared\Domain\Service\OpaqueTokenGeneratorInterface;
use Random\Engine\Mt19937;
use Random\Randomizer;

class OpaqueTokenGenerator
    implements OpaqueTokenGeneratorInterface
{
    public function generate(?int $length = 64, ?int $seed = null): string
    {
        $engine = new Mt19937();
        $randomizer = new Randomizer($engine);

        return bin2hex($randomizer->getBytes($length / 2));
    }
}