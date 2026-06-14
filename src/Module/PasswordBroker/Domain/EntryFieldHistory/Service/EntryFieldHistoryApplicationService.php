<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Domain\EntryFieldHistory\Service;

use App\Module\PasswordBroker\Domain\EntryFieldHistory\Repository\EntryFieldHistoryRepositoryInterface;
use App\Module\PasswordBroker\Infrastructure\EntryFieldHistory\Repository\EntryFieldHistoryRepository;
use Inquisition\Core\Application\Service\ApplicationServiceInterface;
use Inquisition\Foundation\Singleton\SingletonTrait;

class EntryFieldHistoryApplicationService implements ApplicationServiceInterface
{
    use SingletonTrait;

    private EntryFieldHistoryRepositoryInterface $entryFieldHistoryRepository;

    private function __construct()
    {
        $this->entryFieldHistoryRepository = EntryFieldHistoryRepository::getInstance();
    }

}
