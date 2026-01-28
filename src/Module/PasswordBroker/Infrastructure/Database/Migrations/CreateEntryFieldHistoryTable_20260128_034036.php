<?php

namespace App\Module\PasswordBroker\Infrastructure\Database\Migrations;

use Inquisition\Core\Infrastructure\Migration\AbstractMigration;

final readonly class CreateEntryFieldHistoryTable_20260128_034036 extends AbstractMigration
{

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return '0.0.4';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Creating entry field history table';
    }

    /**
     * @return void
     */
    public function up(): void
    {
        $this->query("
            CREATE TABLE `passwordBrokerEntryFieldHistory` (
              `id` VARCHAR(36) NOT NULL PRIMARY KEY,
              `entryFieldId` VARCHAR(36) NOT NULL,
              `eventFieldType` VARCHAR(255) NOT NULL,
              `title` VARCHAR(255) NOT NULL,
              `type` VARCHAR(255) NOT NULL,
              `login` VARCHAR(255) DEFAULT NULL,
              `valueEncrypted` BLOB NOT NULL,
              `initializationVector` BLOB NOT NULL,
              `isDeleted` TINYINT(1) NOT NULL,
              `updatedBy` VARCHAR(36) DEFAULT NULL,
              `createdAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              `updatedAt` TIMESTAMP DEFAULT NULL,
              FOREIGN KEY (`entryFieldId`) REFERENCES `passwordBrokerEntryFields`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              FOREIGN KEY (`updatedBy`) REFERENCES `identityUsers`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
            );
        ");
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->query('DROP TABLE IF EXISTS `passwordBrokerEntryFieldHistory`;');
    }
}