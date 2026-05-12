<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Infrastructure\Database\Migrations;

use Inquisition\Core\Infrastructure\Migration\AbstractMigration;

final readonly class CreateEntriesTable_20260128_032315 extends AbstractMigration
{
    #[\Override]
    public function getVersion(): string
    {
        return '0.0.2';
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'Creating entries table';
    }

    #[\Override]
    public function up(): void
    {
        $this->query("
            CREATE TABLE `passwordBrokerEntries` (
                `id` VARCHAR(36) NOT NULL PRIMARY KEY,
                `entryGroupId` VARCHAR(36) NOT NULL,
                `title` VARCHAR(255) NOT NULL,
                `createdAt` TIMESTAMP NOT NULL,
                `updatedAt` TIMESTAMP DEFAULT NULL,
                `deletedAt` TIMESTAMP DEFAULT NULL,
                FOREIGN KEY (`entryGroupId`) REFERENCES `passwordBrokerEntryGroups`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
            );
        ");
    }

    #[\Override]
    public function down(): void
    {
        $this->query('DROP TABLE IF EXISTS `passwordBrokerEntries`;');
    }
}
