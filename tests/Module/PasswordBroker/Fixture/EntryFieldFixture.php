<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Fixture;

use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\PasswordBroker\Domain\Entry\ValueObject\EntryId;
use App\Module\PasswordBroker\Domain\EntryField\Entity\AbstractEntryField;
use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldFile;
use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldLink;
use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldNote;
use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldPassword;
use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldTotp;
use App\Module\PasswordBroker\Domain\EntryField\Enum\EntryFieldTotpHashAlgorithmEnum;
use App\Module\PasswordBroker\Domain\EntryField\Enum\EntryFieldTypeEnum;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldFileMime;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldFileName;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldFileSize;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldId;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldInitializationVector;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldLogin;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldTag;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldTitle;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldTotpHashAlgorithm;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldTotpTimeout;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldType;
use App\Module\PasswordBroker\Domain\EntryField\ValueObject\EntryFieldValueEncrypted;
use App\Module\PasswordBroker\Infrastructure\EntryField\Repository\EntryFieldRepository;
use App\Shared\Domain\ValueObject\CreatedAt;
use App\Shared\Domain\ValueObject\DateTime;
use App\Shared\Domain\ValueObject\DeletedAt;
use App\Shared\Domain\ValueObject\UpdatedAt;
use DateMalformedStringException;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use InvalidArgumentException;
use Override;
use Tests\Shared\AbstractFixture;

class EntryFieldFixture extends AbstractFixture
{
    public const string ID = EntryFieldRepository::FIELD_ID;
    public const string TITLE = EntryFieldRepository::FIELD_TITLE;
    public const string ENTRY_ID = EntryFieldRepository::FIELD_ENTRY_ID;
    public const string TYPE = EntryFieldRepository::FIELD_TYPE;
    public const string FILE_NAME = EntryFieldRepository::FIELD_FILE_NAME;
    public const string FILE_MIME = EntryFieldRepository::FIELD_FILE_MIME;
    public const string FILE_SIZE = EntryFieldRepository::FIELD_FILE_SIZE;
    public const string LOGIN = EntryFieldRepository::FIELD_LOGIN;
    public const string TOTP_TIMEOUT = EntryFieldRepository::FIELD_TOTP_TIMEOUT;
    public const string TOTP_HASH_ALGORITHM = EntryFieldRepository::FIELD_TOTP_HASH_ALGORITHM;
    public const string VALUE_ENCRYPTED = EntryFieldRepository::FIELD_VALUE_ENCRYPTED;
    public const string TAG = EntryFieldRepository::FIELD_TAG;
    public const string INITIALIZATION_VECTOR = EntryFieldRepository::FIELD_INITIALIZATION_VECTOR;
    public const string CREATED_BY = EntryFieldRepository::FIELD_CREATED_BY;
    public const string UPDATED_BY = EntryFieldRepository::FIELD_UPDATED_BY;
    public const string CREATED_AT = EntryFieldRepository::FIELD_CREATED_AT;
    public const string UPDATED_AT = EntryFieldRepository::FIELD_UPDATED_AT;
    public const string DELETED_AT = EntryFieldRepository::FIELD_DELETED_AT;
    public const string ENTRY = 'entry';

    /**
     * @throws PersistenceException
     */
    #[Override]
    public static function create(array $attributes = [], bool $persist = false): AbstractEntryField
    {
        if (isset($attributes[self::ENTRY]) && !isset($attributes[self::ENTRY_ID])) {
            $attributes[self::ENTRY_ID] = $attributes[self::ENTRY]->getId()->value;
        }
        if (!isset($attributes[self::ENTRY_ID])) {
            $attributes[self::ENTRY_ID] = EntryFixture::create(persist: $persist)->getId()->value;
        }

        $entryFieldId = EntryFieldId::fromRaw(static::generateId($attributes[self::ID]
            ?? EntryFieldId::generate()->toRaw()));

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
        $entryId = EntryId::fromRaw($attributes[self::ENTRY_ID]);
        $valueEncrypted = EntryFieldValueEncrypted::fromRaw($attributes[self::VALUE_ENCRYPTED] ?? static::faker()->password());
        $initializationVector = EntryFieldInitializationVector::fromRaw($attributes[self::INITIALIZATION_VECTOR] ?? static::faker()->password(
            minLength: EntryFieldInitializationVector::IV_LENGTH,
            maxLength: EntryFieldInitializationVector::IV_LENGTH,
        ));
        $tag = EntryFieldTag::fromRaw($attributes[self::TAG] ?? static::faker()->password(
            minLength: EntryFieldTag::TAG_LENGTH,
            maxLength: EntryFieldTag::TAG_LENGTH,
        ));
        $createdAt = CreatedAt::fromRaw($attributes[self::CREATED_AT]
            ?? static::faker()->dateTime()->format(DateTime::FORMAT));
        try {
            $updatedAt = UpdatedAt::fromRaw(
                $attributes[self::UPDATED_AT]
                ?? $createdAt->toDateTime()->modify('+1 day')->format(DateTime::FORMAT),
            );
        } catch (DateMalformedStringException $_) {
            $updatedAt = $createdAt;
        }
        $deletedAt = !empty($attributes[self::DELETED_AT])
            ? DeletedAt::fromRaw($attributes[self::DELETED_AT])
            : null;
        $createdBy = UserId::fromRaw($attributes[self::CREATED_BY] ?? UserId::generate()->toRaw());
        $updatedBy = UserId::fromRaw($attributes[self::UPDATED_BY] ?? UserId::generate()->toRaw());

        $entryField = match ($type->value) {
            EntryFieldTypeEnum::PASSWORD => new EntryFieldPassword(
                id: $entryFieldId,
                entryId: $entryId,
                title: $title,
                valueEncrypted: $valueEncrypted,
                initializationVector: $initializationVector,
                tag: $tag,
                createdAt: $createdAt,
                updatedAt: $updatedAt,
                deletedAt: $deletedAt,
                createdBy: $createdBy,
                updatedBy: $updatedBy,
                login: EntryFieldLogin::fromRaw($attributes[self::LOGIN] ?? static::faker()->userName()),
            ),
            EntryFieldTypeEnum::FILE => new EntryFieldFile(
                id: $entryFieldId,
                entryId: $entryId,
                title: $title,
                valueEncrypted: $valueEncrypted,
                initializationVector: $initializationVector,
                tag: $tag,
                createdAt: $createdAt,
                updatedAt: $updatedAt,
                deletedAt: $deletedAt,
                createdBy: $createdBy,
                updatedBy: $updatedBy,
                fileName: EntryFieldFileName::fromRaw($attributes[self::FILE_NAME] ?? static::faker()->word()),
                fileMime: EntryFieldFileMime::fromRaw($attributes[self::FILE_MIME] ?? static::faker()->mimeType()),
                fileSize: EntryFieldFileSize::fromRaw($attributes[self::FILE_SIZE] ?? static::faker()->numberBetween(100, 1000000)),
            ),
            EntryFieldTypeEnum::TOTP => new EntryFieldTotp(
                id: $entryFieldId,
                entryId: $entryId,
                title: $title,
                valueEncrypted: $valueEncrypted,
                initializationVector: $initializationVector,
                tag: $tag,
                createdAt: $createdAt,
                updatedAt: $updatedAt,
                deletedAt: $deletedAt,
                createdBy: $createdBy,
                updatedBy: $updatedBy,
                totpHashAlgorithm: EntryFieldTotpHashAlgorithm::fromRaw(
                    $attributes[self::TOTP_HASH_ALGORITHM] ?? EntryFieldTotpHashAlgorithmEnum::SHA1->value,
                ),
                totpTimeout: EntryFieldTotpTimeout::fromRaw(
                    $attributes[self::TOTP_TIMEOUT] ?? static::faker()->numberBetween(30, 900),
                ),
            ),
            EntryFieldTypeEnum::LINK => new EntryFieldLink(
                id: $entryFieldId,
                entryId: $entryId,
                title: $title,
                valueEncrypted: $valueEncrypted,
                initializationVector: $initializationVector,
                tag: $tag,
                createdAt: $createdAt,
                updatedAt: $updatedAt,
                deletedAt: $deletedAt,
                createdBy: $createdBy,
                updatedBy: $updatedBy,
            ),
            EntryFieldTypeEnum::NOTE => new EntryFieldNote(
                id: $entryFieldId,
                entryId: $entryId,
                title: $title,
                valueEncrypted: $valueEncrypted,
                initializationVector: $initializationVector,
                tag: $tag,
                createdAt: $createdAt,
                updatedAt: $updatedAt,
                deletedAt: $deletedAt,
                createdBy: $createdBy,
                updatedBy: $updatedBy,
            ),
            default => throw new InvalidArgumentException(sprintf('Entry field type "%s" not supported', $type->toRaw())),
        };

        if ($persist) {
            static::persist($entryField);
        }

        return $entryField;
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
        return EntryFieldRepository::getTableName();
    }

}
