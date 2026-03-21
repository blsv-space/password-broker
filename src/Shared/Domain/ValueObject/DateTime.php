<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use DateTime as DateTimeSystem;
use DateTimeImmutable;
use Inquisition\Core\Domain\ValueObject\AbstractValueObject;
use InvalidArgumentException;

/**
 * @property DateTimeImmutable $value
 */
abstract class DateTime extends AbstractValueObject
{
    public const string FORMAT = 'Y-m-d H:i:s';

    #[\Override]
    public function toRaw(): string
    {
        return $this->value->format(static::FORMAT);
    }

    public function toDateTime(): DateTimeImmutable
    {
        return $this->value;
    }

    /**
     * @inheritDoca
     */
    #[\Override]
    public static function fromRaw(mixed $data): static
    {
        self::validate($data);
        return new static(DateTimeImmutable::createFromFormat(static::FORMAT, $data));
    }

    public static function now(): static
    {
        return new static(new DateTimeImmutable());
    }

    /**
     * @return $this
     */
    public static function fromDateTime(DateTimeImmutable|DateTimeSystem $dateTime): static
    {
        if ($dateTime instanceof DateTimeSystem) {
            return new static(DateTimeImmutable::createFromMutable($dateTime));
        }

        return new static($dateTime);
    }

    #[\Override]
    public static function validate(mixed $data): void
    {
        $dateTimeImmutable = DateTimeImmutable::createFromFormat(static::FORMAT, $data);

        if (!$dateTimeImmutable) {
            throw new InvalidArgumentException('Invalid date format');
        }
    }
}
