<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryGroup\DTO;

use App\Module\PasswordBroker\Domain\EntryGroup\DTO\EntryGroupTreeNode;
use Inquisition\Core\Application\DTO\EntityResponseInterface;
use Inquisition\Core\Domain\Entity\EntityInterface;
use InvalidArgumentException;

class EntryGroupTreeResponse implements EntityResponseInterface
{
    public const string FIELD_TREES = 'trees';

    private EntryGroupTreeNode $entryGroupTreeNode;

    #[\Override]
    public static function fromEntity(EntityInterface $entity): static
    {
        if (!$entity instanceof EntryGroupTreeNode) {
            throw new InvalidArgumentException('Invalid entity type');
        }

        $entryGroupResponse = new static();
        $entryGroupResponse->entryGroupTreeNode = $entity;

        return $entryGroupResponse;
    }

    #[\Override]
    public function getAsArray(): array
    {

        return $this->entryGroupTreeNode->getAsArray();
    }
}
