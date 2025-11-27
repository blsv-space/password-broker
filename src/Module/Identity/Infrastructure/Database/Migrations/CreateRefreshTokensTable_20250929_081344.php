<?php

namespace App\Module\Identity\Infrastructure\Database\Migrations;

use Inquisition\Core\Infrastructure\Migration\AbstractMigration;

readonly class CreateRefreshTokensTable_20250929_081344 extends AbstractMigration
{
    public function up(): void
    {
        $this->query('
            CREATE TABLE `identityRefreshTokens` (
                `id` VARCHAR(36) NOT NULL PRIMARY KEY,
                `userId` varchar(255) NOT NULL,
                `token` varchar(255) NOT NULL,
                `createdAt` datatime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `expirationAt` datatime NOT NULL,
                FOREIGN KEY (`userId`) REFERENCES `identityUsers`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
            );    
        ');
    }
    public function down(): void
    {
        $this->query('DROP TABLE IF EXISTS `identity_refresh_tokens`;');
    }

    public function getVersion(): string
    {
        return '0.0.1';
    }

    public function getDescription(): string
    {
        return 'Creating refresh token table';
    }
}