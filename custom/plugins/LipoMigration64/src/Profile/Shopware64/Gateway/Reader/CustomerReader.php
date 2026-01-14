<?php declare(strict_types=1);

namespace LipoMigration64\Profile\Shopware64\Gateway\Reader;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader\AbstractReader;
use LipoMigration64\Profile\Shopware64\DataSelection\DataSet\CustomerDataSet;
use LipoMigration64\Profile\Shopware64\Gateway\Shopware64LocalGateway;
use LipoMigration64\Profile\Shopware64\Shopware64Profile;

class CustomerReader extends AbstractReader
{
    protected ?Connection $connection = null;

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof Shopware64Profile
            && $migrationContext->getDataSet() !== null
            && $migrationContext->getDataSet()::getEntity() === CustomerDataSet::getEntity();
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
            ->from('customer')
            ->executeQuery();

        $total = 0;
        if ($query instanceof Result) {
            $total = (int) $query->fetchOne();
        }

        return new TotalStruct(CustomerDataSet::getEntity(), $total);
    }

    /**
     * Reads customers from Shopware 6.4.20 database
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
            'LOWER(HEX(c.id)) as id',
            'LOWER(HEX(c.customer_group_id)) as customer_group_id',
            'LOWER(HEX(c.default_payment_method_id)) as default_payment_method_id',
            'LOWER(HEX(c.sales_channel_id)) as sales_channel_id',
            'LOWER(HEX(c.language_id)) as language_id',
            'LOWER(HEX(c.default_billing_address_id)) as default_billing_address_id',
            'LOWER(HEX(c.default_shipping_address_id)) as default_shipping_address_id',
            'c.customer_number',
            'c.salutation_id',
            'c.first_name',
            'c.last_name',
            'c.company',
            'c.email',
            'c.title',
            'c.active',
            'c.guest',
            'c.first_login',
            'c.last_login',
            'c.newsletter',
            'c.birthday',
            'c.last_order_date',
            'c.order_count',
            'c.order_total_amount',
            'c.custom_fields',
            'c.created_at',
            'c.updated_at',
        ])
            ->from('customer', 'c')
            ->setFirstResult($migrationContext->getOffset())
            ->setMaxResults($migrationContext->getLimit());

        $result = $query->executeQuery();
        $customers = $result->fetchAllAssociative();

        // Fetch addresses for each customer
        foreach ($customers as &$customer) {
            $customer['addresses'] = $this->fetchAddresses($customer['id']);
        }

        return $customers;
    }

    /**
     * Fetch customer addresses
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchAddresses(string $customerId): array
    {
        if ($this->connection === null) {
            return [];
        }

        $query = $this->connection->createQueryBuilder();
        $query->select([
            'LOWER(HEX(a.id)) as id',
            'LOWER(HEX(a.customer_id)) as customer_id',
            'LOWER(HEX(a.country_id)) as country_id',
            'LOWER(HEX(a.country_state_id)) as country_state_id',
            'LOWER(HEX(a.salutation_id)) as salutation_id',
            'a.first_name',
            'a.last_name',
            'a.zipcode',
            'a.city',
            'a.company',
            'a.street',
            'a.department',
            'a.title',
            'a.phone_number',
            'a.additional_address_line1',
            'a.additional_address_line2',
            'a.custom_fields',
            'a.created_at',
            'a.updated_at',
        ])
            ->from('customer_address', 'a')
            ->where('LOWER(HEX(a.customer_id)) = :customerId')
            ->setParameter('customerId', $customerId);

        $result = $query->executeQuery();

        return $result->fetchAllAssociative();
    }
}
