<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryFieldHistory\Service\Exception;

class EntryFieldHistoryNotFountException extends EntryFieldHistoryException
{
    public function __construct(string $entryFieldId)
    {
        parent::__construct("Entry field history with ID '$entryFieldId' not found");
    }
}
