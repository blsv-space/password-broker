<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Domain\EntryGroup\ValueObject;

use App\Shared\Domain\ValueObject\Id;
use Inquisition\Core\Domain\ValueObject\AbstractValueObject;
use InvalidArgumentException;

class MaterializedPath extends AbstractValueObject
{
    public const string SEPARATOR = '.';

    #[\Override]
    public function toRaw(): mixed
    {
        return $this->value;
    }

    #[\Override]
    public static function fromRaw(mixed $data): static
    {
        static::validate($data);
        return new static($data);
    }

    #[\Override]
    public static function validate(mixed $data): void
    {
        if (empty($data)) {
            return;
        }
        $path = explode(self::SEPARATOR, $data);
        foreach ($path as $part) {
            try {
                ID::validate($part);
            } catch (InvalidArgumentException $_) {
                throw new InvalidArgumentException('Invalid materialized path format');
            }
        }
    }
}
