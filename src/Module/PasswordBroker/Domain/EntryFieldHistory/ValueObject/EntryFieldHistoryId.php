<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Domain\EntryFieldHistory\ValueObject;

use App\Shared\Domain\ValueObject\Id;
use Inquisition\Core\Domain\ValueObject\ValueObjectInterface;

class EntryFieldHistoryId extends Id implements ValueObjectInterface {}
