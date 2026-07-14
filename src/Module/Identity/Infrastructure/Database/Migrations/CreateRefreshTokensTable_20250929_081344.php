<?php

declare(strict_types=1);

namespace App\Module\Identity\Infrastructure\Database\Migrations;

use Inquisition\Core\Infrastructure\Migration\AbstractMigration;

readonly class CreateRefreshTokensTable_20250929_081344 extends AbstractMigration
{
    #[\Override]
    public function up(): void
    {
        $this->query('
            CREATE TABLE `identityRefreshTokens` (
                `id` VARCHAR(36) NOT NULL PRIMARY KEY,
                `userId` varchar(255) NOT NULL,
                `token` varchar(255) NOT NULL,
                `createdAt` datatime NOT NULL,
                `expirationAt` datatime NOT NULL,
                FOREIGN KEY (`userId`) REFERENCES `identityUsers`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
            );    
        ');
    }
    #[\Override]
    public function down(): void
    {
        $this->query('DROP TABLE IF EXISTS `identity_refresh_tokens`;');
    }

    #[\Override]
    public function getVersion(): string
    {
        return '0.0.1';
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'Creating refresh token table';
    }
}
