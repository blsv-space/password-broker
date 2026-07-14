<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Fixture;

use App\Module\PasswordBroker\Domain\Entry\Entity\Entry;
use App\Module\PasswordBroker\Domain\Entry\ValueObject\EntryId;
use App\Module\PasswordBroker\Domain\Entry\ValueObject\EntryTitle;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupId;
use App\Module\PasswordBroker\Infrastructure\Entry\Repository\EntryRepository;
use App\Shared\Domain\ValueObject\CreatedAt;
use App\Shared\Domain\ValueObject\DateTime;
use App\Shared\Domain\ValueObject\DeletedAt;
use App\Shared\Domain\ValueObject\UpdatedAt;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Shared\AbstractFixture;

class EntryFixture extends AbstractFixture
{
    public const string ID = EntryRepository::FIELD_ID;
    public const string TITLE = EntryRepository::FIELD_TITLE;
    public const string ENTRY_GROUP_ID = EntryRepository::FIELD_ENTRY_GROUP_ID;
    public const string CREATED_AT = EntryRepository::FIELD_CREATED_AT;
    public const string UPDATED_AT = EntryRepository::FIELD_UPDATED_AT;
    public const string DELETED_AT = EntryRepository::FIELD_DELETED_AT;
    public const string ENTRY_GROUP = 'entryGroup';

    /**
     * @throws PersistenceException
     */
    #[\Override]
    public static function create(array $attributes = [], bool $persist = false): Entry
    {
        if (isset($attributes[self::ENTRY_GROUP]) && !isset($attributes[self::ENTRY_GROUP_ID])) {
            $attributes[self::ENTRY_GROUP_ID] = $attributes[self::ENTRY_GROUP]->getId()->value;
        }
        if (!isset($attributes[self::ENTRY_GROUP_ID])) {
            $attributes[self::ENTRY_GROUP_ID] = EntryGroupFixture::create(persist: $persist)->getId()->value;
        }

        $entryId = EntryId::fromRaw(static::generateId($attributes[self::ID] ?? EntryId::generate()->toRaw()));


        $entry = new Entry(
            id: $entryId,
            entryGroupId: EntryGroupId::fromRaw($attributes[self::ENTRY_GROUP_ID]),
            title: EntryTitle::fromRaw($attributes[self::TITLE] ?? static::faker()->word()),
            createdAt: CreatedAt::fromRaw($attributes[self::CREATED_AT]
                ?? static::faker()->dateTime()->format(DateTime::FORMAT)),
            updatedAt: UpdatedAt::fromRaw($attributes[self::UPDATED_AT]
                ?? static::faker()->dateTime()->format(DateTime::FORMAT)),
            deletedAt: !empty($attributes[self::DELETED_AT])
                ? DeletedAt::fromRaw($attributes[self::DELETED_AT])
                : null,
        );

        if ($persist) {
            static::persist($entry);
        }

        return $entry;
    }

    /**
     * @throws PersistenceException
     */
    #[\Override]
    public static function createMany(int $count, array $attributes = [], bool $persist = true): array
    {
        $out = [];
        for ($i = 0; $i < $count; $i++) {
            $out[] = static::create($attributes, $persist);
        }

        return $out;
    }

    #[\Override]
    public static function getTableName(): string
    {
        return EntryRepository::getTableName();
    }

}
