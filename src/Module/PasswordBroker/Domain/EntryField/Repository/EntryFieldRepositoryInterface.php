<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Domain\EntryField\Repository;

use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryField;
use App\Shared\Infrastructure\Repository\EntityRepositoryInterface;
use Inquisition\Core\Domain\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<EntryField>
 * @extends EntityRepositoryInterface<EntryField>
 */
interface EntryFieldRepositoryInterface extends RepositoryInterface, EntityRepositoryInterface {}
