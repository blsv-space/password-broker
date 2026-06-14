<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity;

use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\PasswordBroker\Domain\EntryField\Enum\EntryFieldTypeEnum;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldId;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldInitializationVector;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldLogin;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldTag;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldTitle;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldType;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldValueEncrypted;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\ValueObject\EntryFieldHistoryEventName;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\ValueObject\EntryFieldHistoryId;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\ValueObject\EntryFieldHistoryIsDeleted;
use App\Shared\Domain\ValueObject\CreatedAt;

final class EntryFieldHistoryPassword extends AbstractEntryFieldHistory
{
    public function __construct(
        EntryFieldHistoryId $id,
        EntryFieldId $entryFieldId,
        EntryFieldHistoryEventName $eventName,
        EntryFieldTitle $title,
        EntryFieldValueEncrypted $valueEncrypted,
        EntryFieldInitializationVector $initializationVector,
        EntryFieldTag $tag,
        EntryFieldHistoryIsDeleted $isDeleted,
        UserId $createdBy,
        CreatedAt $createdAt,
        public readonly EntryFieldLogin $login,
    ) {
        parent::__construct(
            id: $id,
            entryFieldId: $entryFieldId,
            eventName: $eventName,
            title: $title,
            type: EntryFieldType::fromRaw(EntryFieldTypeEnum::PASSWORD),
            valueEncrypted: $valueEncrypted,
            initializationVector: $initializationVector,
            tag: $tag,
            isDeleted: $isDeleted,
            createdBy: $createdBy,
            createdAt: $createdAt,
        );
    }
}
