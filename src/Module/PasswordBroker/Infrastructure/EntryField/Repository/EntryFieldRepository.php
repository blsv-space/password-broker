<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Infrastructure\EntryField\Repository;

use App\Module\Identity\Domain\User\ValueObject\UserId;
use App\Module\PasswordBroker\Domain\Entry\Entity\Entry;
use App\Module\PasswordBroker\Domain\Entry\ValueObject\EntryId;
use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryField;
use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldFile;
use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldLink;
use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldNote;
use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldPassword;
use App\Module\PasswordBroker\Domain\EntryField\Entity\EntryFieldTotp;
use App\Module\PasswordBroker\Domain\EntryField\Enum\EntryFieldTypeEnum;
use App\Module\PasswordBroker\Domain\EntryField\Repository\EntryFieldRepositoryInterface;
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
use App\Module\PasswordBroker\Infrastructure\Repository\AbstractPasswordBrokerRepository;
use App\Shared\Domain\ValueObject\CreatedAt;
use App\Shared\Domain\ValueObject\DeletedAt;
use App\Shared\Domain\ValueObject\UpdatedAt;
use App\Shared\Infrastructure\Repository\RepositorySoftDeleteInterface;
use App\Shared\Infrastructure\Repository\RepositorySoftDeleteTrait;
use Inquisition\Core\Domain\Entity\EntityInterface;
use Inquisition\Core\Domain\ValueObject\ValueObjectInterface;
use Inquisition\Foundation\Singleton\SingletonTrait;
use InvalidArgumentException;

/**
 * @method EntryField|null findById(ValueObjectInterface $id)
 *
 * @extends AbstractPasswordBrokerRepository<EntryField>
 * @implements RepositorySoftDeleteInterface<EntryField>
 */
class EntryFieldRepository extends AbstractPasswordBrokerRepository implements EntryFieldRepositoryInterface, RepositorySoftDeleteInterface
{
    use SingletonTrait;

    /**
     * @use RepositorySoftDeleteTrait<EntryField>
     */
    use RepositorySoftDeleteTrait;

    public const string FIELD_ID = 'id';
    public const string FIELD_ENTRY_ID = 'entryId';
    public const string FIELD_TYPE = 'type';
    public const string FIELD_TITLE = 'title';
    public const string FIELD_FILE_NAME = 'fileName';
    public const string FIELD_FILE_MIME = 'fileMime';
    public const string FIELD_FILE_SIZE = 'fileSize';
    public const string FIELD_LOGIN = 'login';
    public const string FIELD_TOTP_TIMEOUT = 'totpTimeout';
    public const string FIELD_TOTP_HASH_ALGORITHM = 'totpHashAlgorithm';
    public const string FIELD_VALUE_ENCRYPTED = 'valueEncrypted';
    public const string FIELD_TAG = 'tag';
    public const string FIELD_INITIALIZATION_VECTOR = 'initializationVector';
    public const string FIELD_CREATED_BY = 'createdBy';
    public const string FIELD_UPDATED_BY = 'updatedBy';
    public const string FIELD_CREATED_AT = 'createdAt';
    public const string FIELD_UPDATED_AT = 'updatedAt';
    public const string FIELD_DELETED_AT = 'deletedAt';

    protected const string TABLE_NAME = 'entryFields';
    protected const string ENTITY_CLASS_NAME = EntryField::class;

    private function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws InvalidArgumentException
     * @return EntryField
     */
    #[\Override]
    protected function mapRowToEntity(array $row): EntityInterface
    {
        $id = EntryFieldId::fromRaw($row[self::FIELD_ID]);
        $entryId = EntryId::fromRaw($row[self::FIELD_ENTRY_ID]);
        $entryFieldType = EntryFieldType::fromRaw($row[self::FIELD_TYPE]);
        $entryFieldTitle = EntryFieldTitle::fromRaw($row[self::FIELD_TITLE]);
        $entryFieldValueEncrypted = EntryFieldValueEncrypted::fromRaw($row[self::FIELD_VALUE_ENCRYPTED]);
        $entryFieldInitializationVector = EntryFieldInitializationVector::fromRaw($row[self::FIELD_INITIALIZATION_VECTOR]);
        $entryFieldTag = EntryFieldTag::fromRaw($row[self::FIELD_TAG]);
        $createdAt = CreatedAt::fromRaw($row[self::FIELD_CREATED_AT]);
        $updatedAt = !empty($row[self::FIELD_UPDATED_AT])
            ? UpdatedAt::fromRaw($row[self::FIELD_UPDATED_AT])
            : null;
        $deletedAt = !empty($row[self::FIELD_DELETED_AT])
            ? DeletedAt::fromRaw($row[self::FIELD_DELETED_AT])
            : null;
        $createdBy = UserId::fromRaw($row[self::FIELD_CREATED_BY]);
        $updatedBy = !empty($row[self::FIELD_UPDATED_BY])
            ? UserId::fromRaw($row[self::FIELD_UPDATED_BY])
            : null;

        return match ($entryFieldType->value) {
            EntryFieldTypeEnum::PASSWORD => new EntryFieldPassword(
                id: $id,
                entryId: $entryId,
                title: $entryFieldTitle,
                valueEncrypted: $entryFieldValueEncrypted,
                initializationVector: $entryFieldInitializationVector,
                tag: $entryFieldTag,
                createdAt: $createdAt,
                updatedAt: $updatedAt,
                deletedAt: $deletedAt,
                createdBy: $createdBy,
                updatedBy: $updatedBy,
                login: EntryFieldLogin::fromRaw($row[self::FIELD_LOGIN]),
            ),
            EntryFieldTypeEnum::FILE => new EntryFieldFile(
                id: $id,
                entryId: $entryId,
                title: $entryFieldTitle,
                valueEncrypted: $entryFieldValueEncrypted,
                initializationVector: $entryFieldInitializationVector,
                tag: $entryFieldTag,
                createdAt: $createdAt,
                updatedAt: $updatedAt,
                deletedAt: $deletedAt,
                createdBy: $createdBy,
                updatedBy: $updatedBy,
                fileName: EntryFieldFileName::fromRaw($row[self::FIELD_FILE_NAME]),
                fileMime: EntryFieldFileMime::fromRaw($row[self::FIELD_FILE_MIME]),
                fileSize: EntryFieldFileSize::fromRaw($row[self::FIELD_FILE_SIZE]),
            ),
            EntryFieldTypeEnum::LINK => new EntryFieldLink(
                id: $id,
                entryId: $entryId,
                title: $entryFieldTitle,
                valueEncrypted: $entryFieldValueEncrypted,
                initializationVector: $entryFieldInitializationVector,
                tag: $entryFieldTag,
                createdAt: $createdAt,
                updatedAt: $updatedAt,
                deletedAt: $deletedAt,
                createdBy: $createdBy,
                updatedBy: $updatedBy,
            ),
            EntryFieldTypeEnum::TOTP => new EntryFieldTotp(
                id: $id,
                entryId: $entryId,
                title: $entryFieldTitle,
                valueEncrypted: $entryFieldValueEncrypted,
                initializationVector: $entryFieldInitializationVector,
                tag: $entryFieldTag,
                createdAt: $createdAt,
                updatedAt: $updatedAt,
                deletedAt: $deletedAt,
                createdBy: $createdBy,
                updatedBy: $updatedBy,
                totpHashAlgorithm: EntryFieldTotpHashAlgorithm::fromRaw($row[self::FIELD_TOTP_HASH_ALGORITHM]),
                totpTimeout: EntryFieldTotpTimeout::fromRaw($row[self::FIELD_TOTP_TIMEOUT]),
            ),
            EntryFieldTypeEnum::NOTE => new EntryFieldNote(
                id: $id,
                entryId: $entryId,
                title: $entryFieldTitle,
                valueEncrypted: $entryFieldValueEncrypted,
                initializationVector: $entryFieldInitializationVector,
                tag: $entryFieldTag,
                createdAt: $createdAt,
                updatedAt: $updatedAt,
                deletedAt: $deletedAt,
                createdBy: $createdBy,
                updatedBy: $updatedBy,
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
    public function mapArrayToEntity(array $array): EntryField
    {
        return $this->mapRowToEntity($array);
    }
}
