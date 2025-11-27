<?php

namespace App\Module\Identity\Domain\User\DTO;

use App\Module\Identity\Domain\User\ValueObject\UserId;
use Inquisition\Core\Domain\Entity\BaseEntity;

class JwtTokenPayloadDTO extends BaseEntity
{
    public function __construct(
        public UserId $userId
    ) {
    }

}