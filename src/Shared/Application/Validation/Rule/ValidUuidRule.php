<?php

declare(strict_types=1);

namespace App\Shared\Application\Validation\Rule;

use App\Shared\Infrastructure\Security\UuidGenerator;
use Inquisition\Core\Application\Validation\RuleInterface;

final readonly class ValidUuidRule implements RuleInterface
{
    #[\Override]
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

    #[\Override]
    public function message(): string
    {
        return 'Invalid UUID format';
    }

    #[\Override]
    public function getName(): string
    {
        return 'valid_uuid';
    }
}
