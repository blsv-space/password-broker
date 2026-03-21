<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Fixture;

use App\Module\PasswordBroker\Domain\EntryGroup\Entity\EntryGroup;
use App\Module\PasswordBroker\Domain\EntryGroup\Service\EntryGroupDomainService;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupId;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupName;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\MaterializedPath;
use App\Module\PasswordBroker\Infrastructure\EntryGroup\EntryGroupRepository;
use App\Shared\Domain\ValueObject\CreatedAt;
use App\Shared\Domain\ValueObject\DateTime;
use App\Shared\Domain\ValueObject\UpdatedAt;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Tests\Shared\AbstractFixture;

class EntryGroupFixture extends AbstractFixture
{
    public const string ID = EntryGroupRepository::FIELD_ID;
    public const string NAME = EntryGroupRepository::FIELD_NAME;
    public const string PARENT_ENTRY_GROUP_ID = EntryGroupRepository::FIELD_PARENT_ENTRY_GROUP_ID;
    public const string MATERIALIZED_PATH = EntryGroupRepository::FIELD_MATERIALIZED_PATH;
    public const string CREATED_AT = EntryGroupRepository::FIELD_CREATED_AT;
    public const string UPDATED_AT = EntryGroupRepository::FIELD_UPDATED_AT;
    public const string DELETED_AT = EntryGroupRepository::FIELD_DELETED_AT;
    public const string PARENT_ENTRY_GROUP = 'parentEntryGroup';

    /**
     * @throws PersistenceException
     */
    #[\Override]
    public static function create(array $attributes = [], bool $persist = false): EntryGroup
    {
        if (isset($attributes[self::PARENT_ENTRY_GROUP]) && !isset($attributes[self::PARENT_ENTRY_GROUP_ID])) {
            $attributes[self::PARENT_ENTRY_GROUP_ID] = $attributes[self::PARENT_ENTRY_GROUP]->getId()->value;
        }

        $entryGroupId = EntryGroupId::fromRaw(static::generateId($attributes[self::ID] ?? EntryGroupId::generate()->toRaw()));
        $entryGroupDomainService = EntryGroupDomainService::getInstance();
        $materializedPath = !empty($attributes[self::MATERIALIZED_PATH])
            ? MaterializedPath::fromRaw($attributes[self::MATERIALIZED_PATH])
            : $entryGroupDomainService->makeMaterializedPath(
                entryGroupId: $entryGroupId,
                parentEntryGroup: $attributes[self::PARENT_ENTRY_GROUP] ?? null,
            );

        $entryGroup = new EntryGroup(
            id: $entryGroupId,
            name: EntryGroupName::fromRaw($attributes[self::NAME] ?? static::faker()->word()),
            materializedPath: $materializedPath,
            parentEntryGroupId: isset($attributes[self::PARENT_ENTRY_GROUP_ID])
                ? EntryGroupId::fromRaw($attributes[self::PARENT_ENTRY_GROUP_ID])
                : null,
            createdAt: CreatedAt::fromRaw($attributes[self::CREATED_AT]
                ?? static::faker()->dateTime()->format(DateTime::FORMAT)),
            updatedAt: UpdatedAt::fromRaw($attributes[self::UPDATED_AT]
                ?? static::faker()->dateTime()->format(DateTime::FORMAT)),
            deletedAt: isset($attributes[self::DELETED_AT])
                ? DateTime::fromRaw($attributes[self::DELETED_AT])
                : null,
        );

        if ($persist) {
            static::persist($entryGroup);
        }

        return $entryGroup;
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
        return EntryGroupRepository::getTableName();
    }

    /**
     * @throws PersistenceException
     */
    public static function createTree(
        int $depth = 3,
        int $branchesPerLevel = 3,
        ?EntryGroup $root = null,
    ): array {
        $out = [];

        if ($depth === 0) {
            return $out;
        }

        if (is_null($root)) {
            $root = static::create(persist: true);
        }
        $out[$root->getId()->value] = $root;

        foreach (static::createMany(
            count: $branchesPerLevel,
            attributes: [self::PARENT_ENTRY_GROUP => $root],
        ) as $entryGroup) {
            $out[$entryGroup->getId()->value] = $entryGroup;
            $branchesLeft = $branchesPerLevel;
            $out += static::createTree($depth - 1, $branchesLeft, $entryGroup);
        }

        return $out;
    }

}
