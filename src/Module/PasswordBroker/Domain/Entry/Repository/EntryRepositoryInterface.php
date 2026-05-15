<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Domain\Entry\Repository;

use App\Module\PasswordBroker\Domain\Entry\Entity\Entry;
use App\Module\PasswordBroker\Domain\Entry\ValueObject\EntryTitle;
use App\Shared\Infrastructure\Repository\EntityRepositoryInterface;
use Inquisition\Core\Domain\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<Entry>
 * @extends EntityRepositoryInterface<Entry>
 */
interface EntryRepositoryInterface extends RepositoryInterface, EntityRepositoryInterface
{
    public function findEntryByTitle(EntryTitle $title): ?Entry;
}
