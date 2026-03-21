<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Domain\EntryGroup\Service;

use App\Module\PasswordBroker\Domain\EntryGroup\Entity\EntryGroup;
use App\Module\PasswordBroker\Domain\EntryGroup\Repository\EntryGroupRepositoryInterface;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupId;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\MaterializedPath;
use App\Module\PasswordBroker\Infrastructure\EntryGroup\EntryGroupRepository;
use Inquisition\Core\Domain\Service\DomainServiceInterface;
use Inquisition\Foundation\Singleton\SingletonTrait;

final class EntryGroupDomainService implements DomainServiceInterface
{
    use SingletonTrait;
    public const string MATERIALIZED_PATH_SEPARATOR = '.';
    private EntryGroupRepositoryInterface $entryGroupRepository;

    private function __construct()
    {
        $this->entryGroupRepository = EntryGroupRepository::getInstance();
    }

    public function makeMaterializedPath(EntryGroupId $entryGroupId, ?EntryGroup $parentEntryGroup = null): MaterializedPath
    {
        if ($parentEntryGroup === null) {
            return MaterializedPath::fromRaw($entryGroupId->toRaw());
        }

        return MaterializedPath::fromRaw($parentEntryGroup->materializedPath->toRaw()
            . self::MATERIALIZED_PATH_SEPARATOR . $entryGroupId->toRaw());
    }

}
