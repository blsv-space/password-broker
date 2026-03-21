<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Domain\EntryGroup\DTO;

use App\Module\PasswordBroker\Domain\EntryGroup\Entity\EntryGroup;
use Inquisition\Core\Domain\Entity\EntityInterface;

final class EntryGroupTreeNode implements EntityInterface
{
    public const string FIELD_ENTRY_GROUP = 'entryGroup';
    public const string FIELD_CHILDREN = 'children';

    public function __construct(
        public EntryGroup $entryGroup {
            get {
                return $this->entryGroup;
            }
        },
        /**
         * @var array<EntryGroupTreeNode>
         */
        public array      $children = [],
    )
    {

    }

    #[\Override]
    public function equals(EntityInterface $other): bool
    {
        if (!$other instanceof self) {
            return false;
        }

        return $this->entryGroup->equals($other->entryGroup);
    }

    #[\Override]
    public function getEntityType(): string
    {
        return self::class;
    }

    /**
     * @psalm-suppress InvalidReturnType
     */
    #[\Override]
    public function getAsArray(): array
    {

        /**
         * @psalm-suppress InvalidReturnStatement
         */
        return [
            self::FIELD_ENTRY_GROUP => $this->entryGroup->getAsArray(),
            self::FIELD_CHILDREN => array_map(fn(EntryGroupTreeNode $child) => $child->getAsArray(), $this->children),
        ];
    }
}
