<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Domain\Entry\ValueObject;

use App\Shared\Domain\ValueObject\Id;
use Inquisition\Core\Domain\ValueObject\ValueObjectInterface;

class EntryId extends Id implements ValueObjectInterface {}
