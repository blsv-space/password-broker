<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Domain\EntryGroup\ValueObject;

use App\Shared\Domain\ValueObject\Id;
use Inquisition\Core\Domain\ValueObject\ValueObjectInterface;

class EntryGroupId extends Id implements ValueObjectInterface {}
