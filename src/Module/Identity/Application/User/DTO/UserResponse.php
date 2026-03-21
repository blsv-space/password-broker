<?php

declare(strict_types=1);

namespace App\Module\Identity\Application\User\DTO;

use App\Module\Identity\Domain\User\Entity\User;
use App\Module\Identity\Infrastructure\User\Repository\UserRepository;
use Inquisition\Core\Application\DTO\EntityResponseInterface;
use Inquisition\Core\Domain\Entity\EntityInterface;
use InvalidArgumentException;

class UserResponse implements EntityResponseInterface
{
    private User $user;

    /**
     *
     * @throws InvalidArgumentException
     */
    #[\Override]
    public static function fromEntity(EntityInterface $entity): static
    {
        if (!$entity instanceof User) {
            throw new InvalidArgumentException('Invalid entity type');
        }

        $userResponse = new static();
        $userResponse->user = $entity;

        return $userResponse;
    }

    #[\Override]
    public function getAsArray(): array
    {
        return [
            UserRepository::FIELD_ID => $this->user->id->value,
            UserRepository::FIELD_USER_NAME => $this->user->userName->toRaw(),
            UserRepository::FIELD_CREATED_AT => $this->user->createdAt?->toRaw(),
            UserRepository::FIELD_UPDATED_AT => $this->user->updatedAt?->toRaw(),
        ];
    }
}
