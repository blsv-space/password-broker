<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity;

use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldId;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldInitializationVector;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldTag;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldTitle;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldType;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldValueEncrypted;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\ValueObject\EntryFieldHistoryEventName;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\ValueObject\EntryFieldHistoryId;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\ValueObject\EntryFieldHistoryIsDeleted;
use App\Shared\Domain\ValueObject\CreatedAt;
use Inquisition\Core\Domain\Entity\BaseEntityWithId;
use Inquisition\Core\Domain\Entity\EntityInterface;
use Inquisition\Core\Domain\ValueObject\ValueObjectInterface;

abstract class AbstractEntryFieldHistory extends BaseEntityWithId implements EntityInterface
{
    protected function __construct(
        public EntryFieldHistoryId $id {
            get {
                return $this->id;
            }
        },
        public readonly EntryFieldId                   $entryFieldId,
        public readonly EntryFieldHistoryEventName     $eventName,
        public readonly EntryFieldTitle                $title,
        public readonly EntryFieldType                 $type,
        public readonly EntryFieldHistoryIsDeleted     $isDeleted,
        public readonly UserId                         $createdBy,
        public readonly CreatedAt                      $createdAt,
        public EntryFieldValueEncrypted       $valueEncrypted,
        public EntryFieldInitializationVector $initializationVector,
        public EntryFieldTag                  $tag,
    ) {}

    /**
     * @return EntryFieldHistoryId|null
     */
    #[\Override]
    public function getId(): ?ValueObjectInterface
    {
        return $this->id;
    }
}
