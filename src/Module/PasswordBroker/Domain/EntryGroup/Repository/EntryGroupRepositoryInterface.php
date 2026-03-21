<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Domain\EntryGroup\Repository;

use App\Module\PasswordBroker\Domain\EntryGroup\Entity\EntryGroup;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupName;
use App\Shared\Infrastructure\Repository\EntityRepositoryInterface;
use Inquisition\Core\Domain\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<EntryGroup>
 * @extends EntityRepositoryInterface<EntryGroup>
 */
interface EntryGroupRepositoryInterface extends RepositoryInterface, EntityRepositoryInterface
{
    public function findEntryGroupByName(EntryGroupName $entryGroupName): ?EntryGroup;
}
