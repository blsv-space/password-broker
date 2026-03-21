<?php

declare(strict_types=1);

namespace App\Module\Identity\Domain\User\DTO;

use Inquisition\Core\Domain\Entity\BaseEntity;

class LoginResponseDto extends BaseEntity
{
    public function __construct(
        public string $jwtToken,
        public string $refreshToken,
    ) {}

    #[\Override]
    public function getAsArray(): array
    {
        return [
            'jwtToken' => $this->jwtToken,
            'refreshToken' => $this->refreshToken,
        ];
    }
}
