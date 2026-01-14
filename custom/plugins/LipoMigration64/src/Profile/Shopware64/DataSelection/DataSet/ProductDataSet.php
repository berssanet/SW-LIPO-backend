<?php declare(strict_types=1);

namespace LipoMigration64\Profile\Shopware64\DataSelection\DataSet;

use SwagMigrationAssistant\Migration\DataSelection\DataSet\DataSet;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use LipoMigration64\Profile\Shopware64\Shopware64Profile;

class ProductDataSet extends DataSet
{
    public static function getEntity(): string
    {
        return 'product';
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof Shopware64Profile;
    }
}
