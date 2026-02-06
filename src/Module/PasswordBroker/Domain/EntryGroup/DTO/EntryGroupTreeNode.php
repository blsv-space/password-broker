<?php

namespace App\Module\PasswordBroker\Domain\EntryGroup\DTO;

use App\Module\PasswordBroker\Domain\EntryGroup\Entity\EntryGroup;
use Inquisition\Core\Domain\Entity\EntityInterface;

final class EntryGroupTreeNode implements EntityInterface
{
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

    /**
     * @param EntityInterface $other
     * @return bool
     */
    public function equals(EntityInterface $other): bool
    {
        if (!$other instanceof self) {
            return false;
        }

        return $this->entryGroup->equals($other->entryGroup);
    }

    /**
     * @return string
     */
    public function getEntityType(): string
    {
        return self::class;
    }

    /**
     * @return array
     */
    public function getAsArray(): array
    {

        return [
            'entryGroup' => $this->entryGroup->getAsArray(),
            'children' => array_map(fn(EntryGroupTreeNode $child) => $child->getAsArray(), $this->children)
        ];
    }
}