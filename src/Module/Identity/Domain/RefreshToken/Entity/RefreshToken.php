<?php

declare(strict_types=1);

namespace App\Module\Identity\Domain\RefreshToken\Entity;

use App\Module\Identity\Domain\RefreshToken\ValueObject\ExpirationAt;
use App\Module\Identity\Domain\RefreshToken\ValueObject\RefreshTokenId;
use App\Module\Identity\Domain\RefreshToken\ValueObject\Token;
use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Shared\Domain\ValueObject\CreatedAt;
use Inquisition\Core\Domain\Entity\BaseEntityWithId;
use Inquisition\Core\Domain\ValueObject\ValueObjectInterface;

class RefreshToken extends BaseEntityWithId
{
    public function __construct(
        public RefreshTokenId $id {
            get {
                return $this->id;
            }
        },
        public UserId          $userId,
        public Token           $token,
        public ExpirationAt    $expirationAt,
        public CreatedAt       $createdAt,
    )
    {
    }

    /**
     * @return RefreshTokenId
     */
    #[\Override]
    public function getId(): ValueObjectInterface
    {
        return $this->id;
    }
}
