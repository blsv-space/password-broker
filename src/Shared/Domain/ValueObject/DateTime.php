<?php

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

    /**
     * @inheritDoc
     */
    public function toRaw(): string
    {
        return $this->value->format(static::FORMAT);
    }

    /**
     * @return DateTimeImmutable
     */
    public function toDateTime(): DateTimeImmutable
    {
        return $this->value;
    }

    /**
     * @inheritDoca
     */
    public static function fromRaw(mixed $data): static
    {
        self::validate($data);
        return new static(DateTimeImmutable::createFromFormat(static::FORMAT, $data));
    }

    /**
     * @return static
     */
    public static function now(): static
    {
        return new static(new DateTimeImmutable());
    }

    /**
     * @param DateTimeImmutable | DateTimeSystem $dateTime
     * @return $this
     */
    public static  function fromDateTime(DateTimeImmutable | DateTimeSystem $dateTime): static
    {
        if ($dateTime instanceof DateTimeSystem) {
            return new static(DateTimeImmutable::createFromMutable($dateTime));
        }

        return new static($dateTime);
    }

    /**
     * @inheritDoc
     */
    public static function validate(mixed $data): void
    {
        $dateTimeImmutable = DateTimeImmutable::createFromFormat(static::FORMAT, $data);

        if (!$dateTimeImmutable) {
            throw new InvalidArgumentException('Invalid date format');
        }
    }
}