<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Infrastructure\EntryFieldHistory\Repository;

use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\PasswordBroker\Domain\EntryField\Enum\EntryFieldTypeEnum;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldId;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldInitializationVector;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldLogin;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldTag;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldTitle;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldTotpHashAlgorithm;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldTotpTimeout;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldType;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldValueEncrypted;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\AbstractEntryFieldHistory;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\EntryFieldHistoryLink;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\EntryFieldHistoryNote;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\EntryFieldHistoryPassword;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\EntryFieldHistoryTotp;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\Repository\EntryFieldHistoryRepositoryInterface;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\ValueObject\EntryFieldHistoryEventName;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\ValueObject\EntryFieldHistoryId;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\ValueObject\EntryFieldHistoryIsDeleted;
use App\Module\PasswordBroker\Infrastructure\Repository\AbstractPasswordBrokerRepository;
use App\Shared\Domain\ValueObject\CreatedAt;
use Inquisition\Core\Domain\Entity\EntityInterface;
use Inquisition\Core\Domain\ValueObject\ValueObjectInterface;
use Inquisition\Foundation\Singleton\SingletonTrait;
use InvalidArgumentException;

/**
 * @method AbstractEntryFieldHistory|null findById(ValueObjectInterface $id)
 *
 * @extends AbstractPasswordBrokerRepository<AbstractEntryFieldHistory>
 */
class EntryFieldHistoryRepository extends AbstractPasswordBrokerRepository implements EntryFieldHistoryRepositoryInterface
{
    use SingletonTrait;

    public const string FIELD_ID = 'id';
    public const string FIELD_ENTRY_FIELD_ID = 'entryFieldId';
    public const string FIELD_EVENT_NAME = 'eventName';
    public const string FIELD_TITLE = 'title';
    public const string FIELD_TYPE = 'type';
    public const string FIELD_LOGIN = 'login';
    public const string FIELD_TOTP_TIMEOUT = 'totpTimeout';
    public const string FIELD_TOTP_HASH_ALGORITHM = 'totpHashAlgorithm';
    public const string FIELD_VALUE_ENCRYPTED = 'valueEncrypted';
    public const string FIELD_TAG = 'tag';
    public const string FIELD_INITIALIZATION_VECTOR = 'initializationVector';
    public const string FIELD_IS_DELETED = 'isDeleted';
    public const string FIELD_CREATED_BY = 'createdBy';

    public const string FIELD_CREATED_AT = 'createdAt';

    protected const string TABLE_NAME = 'entryFieldHistory';
    protected const string ENTITY_CLASS_NAME = AbstractEntryFieldHistory::class;

    private function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws InvalidArgumentException
     * @return AbstractEntryFieldHistory
     */
    #[\Override]
    protected function mapRowToEntity(array $row): EntityInterface
    {
        $id = EntryFieldHistoryId::fromRaw($row[self::FIELD_ID]);
        $entryFieldId = EntryFieldId::fromRaw($row[self::FIELD_ENTRY_FIELD_ID]);
        $eventName = EntryFieldHistoryEventName::fromRaw($row[self::FIELD_EVENT_NAME]);
        $entryFieldType = EntryFieldType::fromRaw($row[self::FIELD_TYPE]);
        $entryFieldTitle = EntryFieldTitle::fromRaw($row[self::FIELD_TITLE]);
        $entryFieldValueEncrypted = EntryFieldValueEncrypted::fromRaw($row[self::FIELD_VALUE_ENCRYPTED]);
        $entryFieldInitializationVector = EntryFieldInitializationVector::fromRaw($row[self::FIELD_INITIALIZATION_VECTOR]);
        $entryFieldTag = EntryFieldTag::fromRaw($row[self::FIELD_TAG]);
        $isDeleted = EntryFieldHistoryIsDeleted::fromRaw((bool) $row[self::FIELD_IS_DELETED]);
        $createdAt = CreatedAt::fromRaw($row[self::FIELD_CREATED_AT]);
        $createdBy = UserId::fromRaw($row[self::FIELD_CREATED_BY]);

        return match ($entryFieldType->value) {
            EntryFieldTypeEnum::PASSWORD => new EntryFieldHistoryPassword(
                id: $id,
                entryFieldId: $entryFieldId,
                eventName: $eventName,
                title: $entryFieldTitle,
                valueEncrypted: $entryFieldValueEncrypted,
                initializationVector: $entryFieldInitializationVector,
                tag: $entryFieldTag,
                isDeleted: $isDeleted,
                createdBy: $createdBy,
                createdAt: $createdAt,
                login: EntryFieldLogin::fromRaw($row[self::FIELD_LOGIN]),
            ),
            EntryFieldTypeEnum::LINK => new EntryFieldHistoryLink(
                id: $id,
                entryFieldId: $entryFieldId,
                eventName: $eventName,
                title: $entryFieldTitle,
                valueEncrypted: $entryFieldValueEncrypted,
                initializationVector: $entryFieldInitializationVector,
                tag: $entryFieldTag,
                isDeleted: $isDeleted,
                createdBy: $createdBy,
                createdAt: $createdAt,
            ),
            EntryFieldTypeEnum::TOTP => new EntryFieldHistoryTotp(
                id: $id,
                entryFieldId: $entryFieldId,
                eventName: $eventName,
                title: $entryFieldTitle,
                valueEncrypted: $entryFieldValueEncrypted,
                initializationVector: $entryFieldInitializationVector,
                tag: $entryFieldTag,
                isDeleted: $isDeleted,
                createdBy: $createdBy,
                createdAt: $createdAt,
                totpHashAlgorithm: EntryFieldTotpHashAlgorithm::fromRaw($row[self::FIELD_TOTP_HASH_ALGORITHM]),
                totpTimeout: EntryFieldTotpTimeout::fromRaw($row[self::FIELD_TOTP_TIMEOUT]),
            ),
            EntryFieldTypeEnum::NOTE => new EntryFieldHistoryNote(
                id: $id,
                entryFieldId: $entryFieldId,
                eventName: $eventName,
                title: $entryFieldTitle,
                valueEncrypted: $entryFieldValueEncrypted,
                initializationVector: $entryFieldInitializationVector,
                tag: $entryFieldTag,
                isDeleted: $isDeleted,
                createdBy: $createdBy,
                createdAt: $createdAt,
            ),
            default => throw new InvalidArgumentException('Unsupported entry field type'),
        };
    }

    #[\Override]
    protected function mapEntityToRow(EntityInterface $entity): array
    {
        return $entity->getAsArray();
    }

    #[\Override]
    public function mapArrayToEntity(array $array): AbstractEntryFieldHistory
    {
        return $this->mapRowToEntity($array);
    }
}
