<?php declare(strict_types=1);

namespace LipoMigration64\Profile\Shopware64\DataSelection;

use SwagMigrationAssistant\Migration\DataSelection\DataSelectionInterface;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionStruct;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use LipoMigration64\Profile\Shopware64\DataSelection\DataSet\ProductDataSet;
use LipoMigration64\Profile\Shopware64\Shopware64Profile;

class ProductDataSelection implements DataSelectionInterface
{
    public const IDENTIFIER = 'products';

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof Shopware64Profile;
    }

    public function getData(): DataSelectionStruct
    {
        return new DataSelectionStruct(
            self::IDENTIFIER,
            $this->getDataSets(),
            $this->getDataSetsRequiredForCount(),
            'swag-migration.index.selectDataCard.dataSelection.products',
            100
        );
    }

    /**
     * Returns all DataSets that should be migrated with this DataSelection
     *
     * @return array<DataSet>
     */
    public function getDataSets(): array
    {
        return [
            new ProductDataSet()
        ];
    }

    /**
     * @return array<DataSet>
     */
    public function getDataSetsRequiredForCount(): array
    {
        return $this->getDataSets();
    }
}
