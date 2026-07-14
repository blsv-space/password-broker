<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Fixture;

use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\PasswordBroker\Domain\EntryField\Entity\AbstractEntryField;
use App\Module\PasswordBroker\Domain\EntryField\Enum\EntryFieldTotpHashAlgorithmEnum;
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
use App\Module\PasswordBroker\Domain\EntryFieldHistory\ValueObject\EntryFieldHistoryEventName;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\ValueObject\EntryFieldHistoryId;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\ValueObject\EntryFieldHistoryIsDeleted;
use App\Module\PasswordBroker\Infrastructure\EntryFieldHistory\Repository\EntryFieldHistoryRepository;
use App\Shared\Domain\ValueObject\CreatedAt;
use App\Shared\Domain\ValueObject\DateTime;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use InvalidArgumentException;
use Override;
use Tests\Shared\AbstractFixture;

class EntryFieldHistoryFixture extends AbstractFixture
{
    public const string ID = EntryFieldHistoryRepository::FIELD_ID;
    public const string TITLE = EntryFieldHistoryRepository::FIELD_TITLE;
    public const string EVENT_NAME = EntryFieldHistoryRepository::FIELD_EVENT_NAME;
    public const string ENTRY_FIELD_ID = EntryFieldHistoryRepository::FIELD_ENTRY_FIELD_ID;
    public const string TYPE = EntryFieldHistoryRepository::FIELD_TYPE;
    public const string LOGIN = EntryFieldHistoryRepository::FIELD_LOGIN;
    public const string TOTP_TIMEOUT = EntryFieldHistoryRepository::FIELD_TOTP_TIMEOUT;
    public const string TOTP_HASH_ALGORITHM = EntryFieldHistoryRepository::FIELD_TOTP_HASH_ALGORITHM;
    public const string VALUE_ENCRYPTED = EntryFieldHistoryRepository::FIELD_VALUE_ENCRYPTED;
    public const string TAG = EntryFieldHistoryRepository::FIELD_TAG;
    public const string INITIALIZATION_VECTOR = EntryFieldHistoryRepository::FIELD_INITIALIZATION_VECTOR;
    public const string IS_DELETED = EntryFieldHistoryRepository::FIELD_IS_DELETED;
    public const string CREATED_BY = EntryFieldHistoryRepository::FIELD_CREATED_BY;
    public const string CREATED_AT = EntryFieldHistoryRepository::FIELD_CREATED_AT;
    public const string ENTRY_FIELD = 'entryField';

    /**
     * @throws PersistenceException
     */
    #[Override]
    public static function create(array $attributes = [], bool $persist = false): AbstractEntryFieldHistory
    {
        if (isset($attributes[self::ENTRY_FIELD]) && $attributes[self::ENTRY_FIELD] instanceof AbstractEntryField) {
            if (!isset($attributes[self::ENTRY_FIELD_ID])) {
                $attributes[self::ENTRY_FIELD_ID] = $attributes[self::ENTRY_FIELD]->getId()->toRaw();
            }
            if (!isset($attributes[self::TYPE])) {
                $attributes[self::TYPE] = $attributes[self::ENTRY_FIELD]->type->toRaw();
            }
            if (!isset($attributes[self::TITLE])) {
                $attributes[self::TITLE] = $attributes[self::ENTRY_FIELD]->title->toRaw();
            }
            if (!isset($attributes[self::VALUE_ENCRYPTED])) {
                $attributes[self::VALUE_ENCRYPTED] = $attributes[self::ENTRY_FIELD]->valueEncrypted->toRaw();
            }
            if (!isset($attributes[self::INITIALIZATION_VECTOR])) {
                $attributes[self::INITIALIZATION_VECTOR] = $attributes[self::ENTRY_FIELD]->initializationVector->toRaw();
            }
            if (!isset($attributes[self::TAG])) {
                $attributes[self::TAG] = $attributes[self::ENTRY_FIELD]->tag->toRaw();
            }
            if (!isset($attributes[self::CREATED_BY])) {
                $attributes[self::CREATED_BY] = $attributes[self::ENTRY_FIELD]->createdBy->toRaw();
            }
        }
        if (!isset($attributes[self::ENTRY_FIELD_ID])) {
            $attributes[self::ENTRY_FIELD_ID] = EntryFieldFixture::create(persist: $persist)->getId()->toRaw();
        }

        $entryFieldHistoryId = EntryFieldHistoryId::fromRaw(static::generateId($attributes[self::ID]
            ?? EntryFieldHistoryId::generate()->toRaw()));

        $type = EntryFieldType::fromRaw(
            isset($attributes[self::TYPE])
            && in_array(
                $attributes[self::TYPE] instanceof EntryFieldTypeEnum
                    ? $attributes[self::TYPE]->value
                    : $attributes[self::TYPE],
                EntryFieldTypeEnum::toArray(),
                true,
            )
                ? $attributes[self::TYPE]
                : EntryFieldTypeEnum::PASSWORD,
        );

        $title = EntryFieldTitle::fromRaw($attributes[self::TITLE] ?? static::faker()->word());
        $eventName = EntryFieldHistoryEventName::fromRaw($attributes[self::EVENT_NAME] ?? 'PasswordBroker.entryField.general.created');
        $entryFieldId = EntryFieldId::fromRaw($attributes[self::ENTRY_FIELD_ID]);
        $valueEncrypted = EntryFieldValueEncrypted::fromRaw($attributes[self::VALUE_ENCRYPTED] ?? static::faker()->password());
        $initializationVector = EntryFieldInitializationVector::fromRaw($attributes[self::INITIALIZATION_VECTOR] ?? static::faker()->password(
            minLength: EntryFieldInitializationVector::IV_LENGTH,
            maxLength: EntryFieldInitializationVector::IV_LENGTH,
        ));
        $tag = EntryFieldTag::fromRaw($attributes[self::TAG] ?? static::faker()->password(
            minLength: EntryFieldTag::TAG_LENGTH,
            maxLength: EntryFieldTag::TAG_LENGTH,
        ));
        $isDeleted = EntryFieldHistoryIsDeleted::fromRaw($attributes[self::IS_DELETED] ?? false);
        $createdAt = CreatedAt::fromRaw($attributes[self::CREATED_AT]
            ?? static::faker()->dateTime()->format(DateTime::FORMAT));
        $createdBy = UserId::fromRaw($attributes[self::CREATED_BY] ?? UserId::generate()->toRaw());

        $entryFieldHistory = match ($type->value) {
            EntryFieldTypeEnum::PASSWORD => new EntryFieldHistoryPassword(
                id: $entryFieldHistoryId,
                entryFieldId: $entryFieldId,
                eventName: $eventName,
                title: $title,
                valueEncrypted: $valueEncrypted,
                initializationVector: $initializationVector,
                tag: $tag,
                isDeleted: $isDeleted,
                createdBy: $createdBy,
                createdAt: $createdAt,
                login: EntryFieldLogin::fromRaw($attributes[self::LOGIN] ?? static::faker()->userName()),
            ),
            EntryFieldTypeEnum::TOTP => new EntryFieldHistoryTotp(
                id: $entryFieldHistoryId,
                entryFieldId: $entryFieldId,
                eventName: $eventName,
                title: $title,
                valueEncrypted: $valueEncrypted,
                initializationVector: $initializationVector,
                tag: $tag,
                isDeleted: $isDeleted,
                createdBy: $createdBy,
                createdAt: $createdAt,
                totpHashAlgorithm: EntryFieldTotpHashAlgorithm::fromRaw(
                    $attributes[self::TOTP_HASH_ALGORITHM] ?? EntryFieldTotpHashAlgorithmEnum::SHA1->value,
                ),
                totpTimeout: EntryFieldTotpTimeout::fromRaw(
                    $attributes[self::TOTP_TIMEOUT] ?? static::faker()->numberBetween(30, 900),
                ),
            ),
            EntryFieldTypeEnum::LINK => new EntryFieldHistoryLink(
                id: $entryFieldHistoryId,
                entryFieldId: $entryFieldId,
                eventName: $eventName,
                title: $title,
                valueEncrypted: $valueEncrypted,
                initializationVector: $initializationVector,
                tag: $tag,
                isDeleted: $isDeleted,
                createdBy: $createdBy,
                createdAt: $createdAt,
            ),
            EntryFieldTypeEnum::NOTE => new EntryFieldHistoryNote(
                id: $entryFieldHistoryId,
                entryFieldId: $entryFieldId,
                eventName: $eventName,
                title: $title,
                valueEncrypted: $valueEncrypted,
                initializationVector: $initializationVector,
                tag: $tag,
                isDeleted: $isDeleted,
                createdBy: $createdBy,
                createdAt: $createdAt,
            ),
            default => throw new InvalidArgumentException(sprintf('Entry field type "%s" not supported', $type->toRaw())),
        };

        if ($persist) {
            static::persist($entryFieldHistory);
        }

        return $entryFieldHistory;
    }

    /**
     * @throws PersistenceException
     */
    #[Override]
    public static function createMany(int $count, array $attributes = [], bool $persist = true): array
    {
        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $out[] = static::create($attributes, $persist);
        }

        return $out;
    }

    #[Override]
    public static function getTableName(): string
    {
        return EntryFieldHistoryRepository::getTableName();
    }

}
