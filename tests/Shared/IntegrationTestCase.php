<?php

declare(strict_types=1);

namespace Tests\Shared;

use PDO;

abstract class IntegrationTestCase extends AbstractTestCase
{
    protected PDO $connection;

}
