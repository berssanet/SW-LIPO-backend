<?php declare(strict_types=1);

namespace LipoMigration64\Profile\Shopware64\DataSelection;

use SwagMigrationAssistant\Migration\DataSelection\DataSelectionInterface;
use SwagMigrationAssistant\Migration\DataSelection\DataSelectionStruct;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use LipoMigration64\Profile\Shopware64\DataSelection\DataSet\CustomerDataSet;
use LipoMigration64\Profile\Shopware64\Shopware64Profile;

class CustomerDataSelection implements DataSelectionInterface
{
    public const IDENTIFIER = 'customers';

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
            'swag-migration.index.selectDataCard.dataSelection.customers',
            200
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
            new CustomerDataSet()
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
