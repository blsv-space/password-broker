<?php

declare(strict_types=1);

namespace Tests\Module\PasswordBroker\Unit\Domain\EntryGroupUser\ValueObject;

use App\Module\PasswordBroker\Domain\EntryGroupUser\ValueObject\EntryGroupUserId;
use InvalidArgumentException;
use Tests\Shared\UnitTestCase;

class EntryGroupUserIdTest extends UnitTestCase
{
    public function test_it_should_create_an_entry_group_user_id(): void
    {
        $rawValue = '019d379d-f4c2-7cc0-bb22-b98ec14369f5';

        $entryGroupUserId = EntryGroupUserId::fromRaw($rawValue);
        $this->assertInstanceOf(EntryGroupUserId::class, $entryGroupUserId);
    }

    public function test_it_should_return_string(): void
    {
        $id = EntryGroupUserId::fromRaw('019d379d-f4c2-7cc0-bb22-b98ec14369f5');
        $this->assertIsString($id->toRaw());
    }

    public function test_it_should_throw_exception_for_invalid_uuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EntryGroupUserId::fromRaw('invalid-uuid');
    }

}
