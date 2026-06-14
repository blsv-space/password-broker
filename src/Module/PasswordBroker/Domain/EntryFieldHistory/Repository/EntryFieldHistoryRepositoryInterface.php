<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Domain\EntryFieldHistory\Repository;

use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\AbstractEntryFieldHistory;
use App\Shared\Infrastructure\Repository\EntityRepositoryInterface;
use Inquisition\Core\Domain\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<AbstractEntryFieldHistory>
 * @extends EntityRepositoryInterface<AbstractEntryFieldHistory>
 */
interface EntryFieldHistoryRepositoryInterface extends RepositoryInterface, EntityRepositoryInterface {}
