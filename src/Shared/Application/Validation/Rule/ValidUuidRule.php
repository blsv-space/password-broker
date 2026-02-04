<?php

namespace App\Shared\Application\Validation\Rule;

use App\Shared\Infrastructure\Security\UuidGenerator;
use Inquisition\Core\Application\Validation\RuleInterface;

final readonly class ValidUuidRule implements RuleInterface
{

    /**
     * @param mixed $value
     * @param array $data
     * @return bool
     */
    public function passes(mixed $value, array $data = []): bool
    {
        if (is_null($value)) {
            return true; // Allow null values, use Required rule if needed
        }

        if (!is_string($value)) {
            return false;
        }

        if (new UuidGenerator()->isValid($value)) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function message(): string
    {
        return 'Invalid UUID format';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'valid_uuid';
    }
}
