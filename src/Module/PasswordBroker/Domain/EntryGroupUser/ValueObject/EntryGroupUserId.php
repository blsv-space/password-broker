<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Domain\EntryGroupUser\ValueObject;

use App\Shared\Domain\ValueObject\Id;
use Inquisition\Core\Domain\ValueObject\ValueObjectInterface;

class EntryGroupUserId extends Id implements ValueObjectInterface {}
