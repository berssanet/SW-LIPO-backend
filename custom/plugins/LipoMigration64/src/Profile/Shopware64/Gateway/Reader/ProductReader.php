<?php declare(strict_types=1);

namespace LipoMigration64\Profile\Shopware64\Gateway\Reader;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\AbstractReader;
use LipoMigration64\Profile\Shopware64\DataSelection\DataSet\ProductDataSet;
use LipoMigration64\Profile\Shopware64\Gateway\Shopware64LocalGateway;
use LipoMigration64\Profile\Shopware64\Shopware64Profile;

class ProductReader extends AbstractReader
{
    protected ?Connection $connection = null;

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof Shopware64Profile
            && $migrationContext->getDataSet() !== null
            && $migrationContext->getDataSet()::getEntity() === ProductDataSet::getEntity();
    }

    public function supportsTotal(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof Shopware64Profile
            && $migrationContext->getGateway()->getName() === Shopware64LocalGateway::GATEWAY_NAME;
    }

    protected function setConnection(MigrationContextInterface $migrationContext): void
    {
        $this->connection = $this->connectionFactory->createDatabaseConnection($migrationContext);
    }

    public function readTotal(MigrationContextInterface $migrationContext): ?TotalStruct
    {
        $this->setConnection($migrationContext);

        if ($this->connection === null) {
            return null;
        }

        $query = $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('product')
            ->executeQuery();

        $total = 0;
        if ($query instanceof Result) {
            $total = (int) $query->fetchOne();
        }

        return new TotalStruct(ProductDataSet::getEntity(), $total);
    }

    /**
     * Reads products from Shopware 6.4.20 database
     * Structure: product table with JSON fields for translations, price, etc.
     *
     * @param array<string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    public function read(MigrationContextInterface $migrationContext, array $params = []): array
    {
        $this->setConnection($migrationContext);

        if ($this->connection === null) {
            return [];
        }

        $query = $this->connection->createQueryBuilder();
        $query->select([
            'LOWER(HEX(p.id)) as id',
            'LOWER(HEX(p.version_id)) as version_id',
            'LOWER(HEX(p.parent_id)) as parent_id',
            'p.product_number',
            'p.stock',
            'p.available_stock',
            'p.active',
            'p.price',
            'LOWER(HEX(p.tax_id)) as tax_id',
            'LOWER(HEX(p.manufacturer_id)) as manufacturer_id',
            'LOWER(HEX(p.unit_id)) as unit_id',
            'LOWER(HEX(p.cover_id)) as cover_id',
            'p.purchase_unit',
            'p.reference_unit',
            'p.weight',
            'p.width',
            'p.height',
            'p.length',
            'p.min_purchase',
            'p.max_purchase',
            'p.purchase_steps',
            'p.is_closeout',
            'p.shipping_free',
            'p.mark_as_topseller',
            'p.created_at',
            'p.updated_at',
            'pt.name',
            'pt.description',
            'pt.meta_title',
            'pt.meta_description',
            'pt.keywords',
            'pt.custom_search_keywords',
        ])
            ->from('product', 'p')
            ->leftJoin(
                'p',
                'product_translation',
                'pt',
                'p.id = pt.product_id AND p.version_id = pt.product_version_id'
            )
            ->setFirstResult($migrationContext->getOffset())
            ->setMaxResults($migrationContext->getLimit());

        $result = $query->executeQuery();

        return $result->fetchAllAssociative();
    }
}
