<?php

namespace App\Module\Identity\Domain\User\DTO;

use Inquisition\Core\Domain\Entity\BaseEntity;

class LoginResponseDTO extends BaseEntity
{
    public function __construct(
        public string $jwtToken,
        public string $refreshToken,
    )
    {
    }

    /**
     * @return array
     */
    public function getAsArray(): array
    {
        return [
            'jwtToken' => $this->jwtToken,
            'refreshToken' => $this->refreshToken,
        ];
    }
}