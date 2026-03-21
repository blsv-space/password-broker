<?php

declare(strict_types=1);

namespace App\Module\Identity\Infrastructure\Database\Migrations;

use Inquisition\Core\Infrastructure\Migration\AbstractMigration;

readonly class CreateUsersTable_20250929_082334 extends AbstractMigration
{
    #[\Override]
    public function up(): void
    {
        $this->query('
            CREATE TABLE `identityUsers` (
                `id` VARCHAR(36) NOT NULL PRIMARY KEY,
                `userName` varchar(255) NOT NULL UNIQUE,
                `hashedPassword` varchar(255) NOT NULL,
                `email` varchar(255) NOT NULL,
                `isAdmin` tinyint(1) NOT NULL DEFAULT 0,
                `publicKey` BLOB NOT NULL,
                `createdAt` datatime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updatedAt` datatime NOT NULL DEFAULT CURRENT_TIMESTAMP
            );    
        ');
    }
    #[\Override]
    public function down(): void
    {
        $this->query('DROP TABLE IF EXISTS `identity_users`;');
    }

    #[\Override]
    public function getVersion(): string
    {
        return '0.0.1';
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'Creating users table';
    }
}
