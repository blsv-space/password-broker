<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Infrastructure\Database\Migrations;

use Inquisition\Core\Infrastructure\Migration\AbstractMigration;

final readonly class CreateEntryGroupUserTable_20260128_031434 extends AbstractMigration
{
    #[\Override]
    public function getVersion(): string
    {
        return '0.0.2';
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'Create entry group users table';
    }

    #[\Override]
    public function up(): void
    {
        $this->query("
            CREATE TABLE `passwordBrokerEntryGroupUser` (
                `id` VARCHAR(36) NOT NULL PRIMARY KEY,
                `entryGroupId` VARCHAR(36) NOT NULL,
                `userId` VARCHAR(36) NOT NULL,
                `role` ENUM('admin', 'moderator', 'member') NOT NULL DEFAULT 'member',
                `encryptedAesPassword` BLOB NOT NULL,
                `createdAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updatedAt` TIMESTAMP DEFAULT NULL,
                FOREIGN KEY (`entryGroupId`) REFERENCES `passwordBrokerEntryGroups`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                FOREIGN KEY (`userId`) REFERENCES `identityUsers`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `idxEntryGroupIdUserId` UNIQUE (`entryGroupId`, `userId`)
            );
        ");
    }

    #[\Override]
    public function down(): void
    {
        $this->query('DROP TABLE IF EXISTS `passwordBrokerEntryGroupUser`;');
    }
}
