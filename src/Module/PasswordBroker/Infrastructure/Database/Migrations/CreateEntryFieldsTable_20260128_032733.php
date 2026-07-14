<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Infrastructure\Database\Migrations;

use Inquisition\Core\Infrastructure\Migration\AbstractMigration;

final readonly class CreateEntryFieldsTable_20260128_032733 extends AbstractMigration
{
    #[\Override]
    public function getVersion(): string
    {
        return '0.0.3';
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'Creating entry fields table';
    }

    #[\Override]
    public function up(): void
    {
        $this->query("
            CREATE TABLE `passwordBrokerEntryFields` (
                `id` VARCHAR(36) NOT NULL PRIMARY KEY,
                `entryId` VARCHAR(36) NOT NULL,
                `type` VARCHAR(255) NOT NULL,
                `title` VARCHAR(255) NOT NULL,
                `fileName` VARCHAR(255) DEFAULT NULL,
                `fileMime` VARCHAR(255) DEFAULT NULL,
                `fileSize` BIGINT DEFAULT NULL,
                `login` VARCHAR(255) DEFAULT NULL,
                `totpTimeout` INT DEFAULT NULL,
                `totpHashAlgorithm` ENUM('sha1', 'sha256', 'sha512') DEFAULT NULL,
                `valueEncrypted` BLOB NOT NULL,
                `tag` BINARY(16) NOT NULL,
                `initializationVector` BINARY(12) NOT NULL,
                `createdBy` VARCHAR(36) DEFAULT NULL,
                `updatedBy` VARCHAR(36) DEFAULT NULL,
                `createdAt` TIMESTAMP NOT NULL,
                `updatedAt` TIMESTAMP DEFAULT NULL,
                `deletedAt` TIMESTAMP DEFAULT NULL,
                FOREIGN KEY (`entryId`) REFERENCES `passwordBrokerEntries`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                FOREIGN KEY (`createdBy`) REFERENCES `identityUsers`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
                FOREIGN KEY (`updatedBy`) REFERENCES `identityUsers`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
            );
        ");
    }

    #[\Override]
    public function down(): void
    {
        $this->query('DROP TABLE IF EXISTS `passwordBrokerEntryFields`;');
    }
}
