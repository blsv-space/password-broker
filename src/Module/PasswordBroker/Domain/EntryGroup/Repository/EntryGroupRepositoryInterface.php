<?php

namespace App\Module\PasswordBroker\Domain\EntryGroup\Repository;

use App\Module\PasswordBroker\Domain\EntryGroup\Entity\EntryGroup;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupName;

interface EntryGroupRepositoryInterface
{
    /**
     * @param EntryGroupName $entryGroupName
     * @return EntryGroup|null
     */
    public function findByEntryGroupName(EntryGroupName $entryGroupName): ?EntryGroup;
}