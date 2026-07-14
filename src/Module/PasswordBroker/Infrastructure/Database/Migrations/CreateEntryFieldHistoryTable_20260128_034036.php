<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Infrastructure\Database\Migrations;

use Inquisition\Core\Infrastructure\Migration\AbstractMigration;

final readonly class CreateEntryFieldHistoryTable_20260128_034036 extends AbstractMigration
{
    #[\Override]
    public function getVersion(): string
    {
        return '0.0.4';
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'Creating entry field history table';
    }

    #[\Override]
    public function up(): void
    {
        $this->query("
            CREATE TABLE `passwordBrokerEntryFieldHistory` (
              `id` VARCHAR(36) NOT NULL PRIMARY KEY,
              `entryFieldId` VARCHAR(36) NOT NULL,
              `eventName` VARCHAR(255) NOT NULL,
              `title` VARCHAR(255) NOT NULL,
              `type` VARCHAR(255) NOT NULL,
              `login` VARCHAR(255) DEFAULT NULL,
              `totpTimeout` INT DEFAULT NULL,
              `totpHashAlgorithm` ENUM('sha1', 'sha256', 'sha512') DEFAULT NULL,
              `valueEncrypted` BLOB NOT NULL,
              `tag` BINARY(16) NOT NULL,
              `initializationVector` BINARY(12) NOT NULL,
              `isDeleted` TINYINT(1) NOT NULL,
              `createdBy` VARCHAR(36) DEFAULT NULL,
              `createdAt` TIMESTAMP NOT NULL,
              FOREIGN KEY (`entryFieldId`) REFERENCES `passwordBrokerEntryFields`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              FOREIGN KEY (`createdBy`) REFERENCES `identityUsers`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
            );
        ");
    }

    #[\Override]
    public function down(): void
    {
        $this->query('DROP TABLE IF EXISTS `passwordBrokerEntryFieldHistory`;');
    }
}
