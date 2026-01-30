<?php

namespace App\Module\PasswordBroker\Application\EntryGroup\Service;

use App\Module\PasswordBroker\Application\EntryGroup\Job\CreateEntryGroupSyncJob;
use App\Module\PasswordBroker\Domain\EntryGroup\Entity\EntryGroup;
use App\Module\PasswordBroker\Domain\EntryGroup\Service\EntryGroupDomainService;
use App\Module\PasswordBroker\Domain\EntryGroup\ValueObject\EntryGroupId;
use Inquisition\Core\Application\Service\ApplicationServiceInterface;
use Inquisition\Core\Infrastructure\Persistence\Exception\PersistenceException;
use Inquisition\Foundation\Singleton\SingletonTrait;
use Throwable;

class EntryGroupApplicationService
    implements ApplicationServiceInterface
{
    private EntryGroupDomainService $entryGroupDomainService;

    use SingletonTrait;

    private function __construct()
    {
        $this->entryGroupDomainService = EntryGroupDomainService::getInstance();
    }

    /**
     * @param string $name
     * @param EntryGroup|null $parentEntryGroup
     * @return EntryGroup
     * @throws Throwable
     */
    public function createEntryGroupSync(
        string $name,
        ?EntryGroup $parentEntryGroup = null,
    ): EntryGroup
    {

        return new CreateEntryGroupSyncJob([
            CreateEntryGroupSyncJob::PAYLOAD_KEY_ID => EntryGroupId::generate()->toRaw(),
            CreateEntryGroupSyncJob::PAYLOAD_KEY_NAME => $name,
            CreateEntryGroupSyncJob::PAYLOAD_KEY_PARENT_ENTRY_GROUP_ID => $parentEntryGroup?->id->toRaw() ?? null,
        ])->execute();
    }
}