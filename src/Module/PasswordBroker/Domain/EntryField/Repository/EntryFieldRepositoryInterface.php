<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Domain\EntryField\Repository;

use App\Module\PasswordBroker\Domain\EntryField\Entity\AbstractEntryField;
use App\Shared\Infrastructure\Repository\EntityRepositoryInterface;
use Inquisition\Core\Domain\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<AbstractEntryField>
 * @extends EntityRepositoryInterface<AbstractEntryField>
 */
interface EntryFieldRepositoryInterface extends RepositoryInterface, EntityRepositoryInterface {}
