<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Application\EntryFieldHistory\Event;

use App\Module\PasswordBroker\Application\Event\AbstractPasswordBrokerEvent;
use App\Module\PasswordBroker\Domain\EntryFieldHistory\Entity\AbstractEntryFieldHistory;
use Inquisition\Core\Application\Event\EventInterface;

final readonly class EntryFieldHistoryDecryptedEvent extends AbstractPasswordBrokerEvent implements EventInterface
{
    public function __construct(
        private AbstractEntryFieldHistory $entryFieldHistory,
        private string                    $executorId,
    ) {
        parent::__construct();
    }

    public function getEntry(): AbstractEntryFieldHistory
    {
        return $this->entryFieldHistory;
    }

    public function getExecutorId(): string
    {
        return $this->executorId;
    }

    #[\Override]
    public function getEventName(): string
    {
        return parent::getEventName() . '.entryFiledHistory.decrypted';
    }

}
