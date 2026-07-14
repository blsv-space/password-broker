<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryField\Service\Exception;

class EntryFieldNotFountException extends EntryFieldException
{
    public function __construct(string $entryFieldId)
    {
        parent::__construct("Entry field with ID '$entryFieldId' not found");
    }
}
