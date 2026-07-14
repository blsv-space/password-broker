<?php

declare(strict_types=1);

namespace App\Shared\Domain\Security\Encryption;

use Inquisition\Foundation\Singleton\SingletonInterface;

interface SaltProviderInterface extends SingletonInterface
{
    public function getSalt(): string;
}
