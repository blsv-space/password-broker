<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception;

use App\Module\PasswordBroker\Domain\EntryGroupUser\ValueObject\EntryGroupUserId;

class EntryGroupUserNotFoundException extends EntryGroupUserApplicationException
{
    public function __construct(EntryGroupUserId $entryGroupUserId)
    {
        parent::__construct("Entry group user with ID {$entryGroupUserId->value} not found");
    }
}
