<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Domain\EntryField\Entity;

use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\PasswordBroker\Domain\Entry\ValueObject\EntryId;
use App\Module\PasswordBroker\Domain\EntryField\Enum\EntryFieldTypeEnum;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldId;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldInitializationVector;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldTag;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldTitle;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldType;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldValueEncrypted;
use App\Shared\Domain\ValueObject\CreatedAt;
use App\Shared\Domain\ValueObject\DeletedAt;
use App\Shared\Domain\ValueObject\UpdatedAt;

final class EntryFieldNote extends AbstractEntryField
{
    public function __construct(
        EntryFieldId $id,
        EntryId      $entryId,
        EntryFieldTitle $title,
        EntryFieldValueEncrypted $valueEncrypted,
        EntryFieldInitializationVector $initializationVector,
        EntryFieldTag $tag,
        ?CreatedAt $createdAt,
        ?UpdatedAt $updatedAt,
        ?DeletedAt $deletedAt,
        UserId $createdBy,
        ?UserId $updatedBy,
    ) {
        parent::__construct(
            id: $id,
            entryId: $entryId,
            type: EntryFieldType::fromRaw(EntryFieldTypeEnum::NOTE),
            title: $title,
            valueEncrypted: $valueEncrypted,
            initializationVector: $initializationVector,
            tag: $tag,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            deletedAt: $deletedAt,
            createdBy: $createdBy,
            updatedBy: $updatedBy,
        );
    }
}
