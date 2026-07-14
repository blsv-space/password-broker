<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryGroupUser\Service\Exception;

use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupId;

class TargetGroupNotFoundException extends EntryGroupUserApplicationException
{
    public function __construct(EntryGroupId $entryGroupId)
    {
        parent::__construct("Target group with ID {$entryGroupId->toRaw()} not found");
    }
}
