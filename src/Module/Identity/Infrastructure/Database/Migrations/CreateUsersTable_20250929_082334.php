<?php

namespace App\Module\Identity\Infrastructure\Database\Migrations;

use Inquisition\Core\Infrastructure\Migration\AbstractMigration;

readonly class CreateUsersTable_20250929_082334 extends AbstractMigration
{

    public function up(): void
    {
        $this->query('
            CREATE TABLE `identityUsers` (
                `id` VARCHAR(36) NOT NULL PRIMARY KEY,
                `userName` varchar(255) NOT NULL UNIQUE,
                `hashedPassword` varchar(255) NOT NULL,
                `createdAt` datatime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updatedAt` datatime NOT NULL DEFAULT CURRENT_TIMESTAMP
            );    
        ');
    }
    public function down(): void
    {
        $this->query('DROP TABLE IF EXISTS `identity_users`;');
    }

    public function getVersion(): string
    {
        return '0.0.1';
    }

    public function getDescription(): string
    {
        return 'Creating users table';
    }
}