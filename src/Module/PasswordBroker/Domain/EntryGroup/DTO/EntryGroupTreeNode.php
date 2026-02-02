<?php

namespace App\Module\PasswordBroker\Domain\EntryGroup\DTO;

use App\Module\PasswordBroker\Domain\EntryGroup\Entity\EntryGroup;

final class EntryGroupTreeNode
{
    public function __construct(
        public EntryGroup $entryGroup {
            get {
                return $this->entryGroup;
            }
        },
        public array      $children = [],
    )
    {

    }

}