<?php

namespace App\Module\PasswordBroker\Domain\EntryGroup\Repository;

use App\Module\PasswordBroker\Domain\EntryGroup\Entity\EntryGroup;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupName;
use Inquisition\Core\Domain\Repository\RepositoryInterface;

interface EntryGroupRepositoryInterface extends RepositoryInterface
{
    /**
     * @param EntryGroupName $entryGroupName
     * @return EntryGroup|null
     */
    public function findEntryGroupByName(EntryGroupName $entryGroupName): ?EntryGroup;
}