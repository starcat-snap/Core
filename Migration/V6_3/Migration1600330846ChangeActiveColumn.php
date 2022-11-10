<?php declare(strict_types=1);

namespace Shopware\Core\Migration\V6_3;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @package core
 *
 * @deprecated tag:v6.5.0 - reason:becomes-internal - Migrations will be internal in v6.5.0
 */
class Migration1600330846ChangeActiveColumn extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1600330846;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `product` CHANGE `active` `active` tinyint unsigned NULL AFTER `product_number`;');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
