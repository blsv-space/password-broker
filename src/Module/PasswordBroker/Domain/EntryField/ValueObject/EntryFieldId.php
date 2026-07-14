<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Domain\EntryField\ValueObject;

use App\Shared\Domain\ValueObject\Id;
use Inquisition\Core\Domain\ValueObject\ValueObjectInterface;

class EntryFieldId extends Id implements ValueObjectInterface {}
