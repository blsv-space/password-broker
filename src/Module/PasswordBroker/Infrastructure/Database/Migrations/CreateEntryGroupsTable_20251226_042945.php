<?php

declare(strict_types=1);

namespace App\Module\PasswordBroker\Infrastructure\Database\Migrations;

use Inquisition\Core\Infrastructure\Migration\AbstractMigration;

final readonly class CreateEntryGroupsTable_20251226_042945 extends AbstractMigration
{
    #[\Override]
    public function getVersion(): string
    {
        return  '0.0.1';
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'Create entry groups table';
    }

    #[\Override]
    public function up(): void
    {
        $this->query('
            CREATE TABLE `passwordBrokerEntryGroups` (
                `id` VARCHAR(36) NOT NULL PRIMARY KEY,
                `parentEntryGroupId` VARCHAR(36) DEFAULT NULL,
                `materializedPath` VARCHAR(1024) NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `createdAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updatedAt` TIMESTAMP DEFAULT NULL,
                `deletedAt` TIMESTAMP DEFAULT NULL,
                FOREIGN KEY (`parentEntryGroupId`) 
                    References `passwordBrokerEntryGroups`(`id`) 
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
            );
            CREATE INDEX `idxMaterializedPath` ON `passwordBrokerEntryGroups` (`materializedPath`);
        ');
    }

    #[\Override]
    public function down(): void
    {
        $this->query('DROP TABLE IF EXISTS `passwordBrokerEntryGroups`;');
    }
}
