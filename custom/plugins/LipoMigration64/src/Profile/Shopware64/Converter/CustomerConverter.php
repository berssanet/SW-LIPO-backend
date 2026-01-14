<?php declare(strict_types=1);

namespace LipoMigration64\Profile\Shopware64\Converter;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\Converter\Converter;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use LipoMigration64\Profile\Shopware64\DataSelection\DataSet\CustomerDataSet;
use LipoMigration64\Profile\Shopware64\Shopware64Profile;

class CustomerConverter extends Converter
{
    private string $connectionId = '';
    private string $runId = '';
    private Context $context;

    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService
    ) {
        parent::__construct($mappingService, $loggingService);
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof Shopware64Profile
            && $migrationContext->getDataSet() !== null
            && $migrationContext->getDataSet()::getEntity() === CustomerDataSet::getEntity();
    }

    public function getSourceIdentifier(array $data): string
    {
        return $data['id'];
    }

    /**
     * Converts Shopware 6.4.20 customer data to 6.7 structure
     *
     * @param array<string, mixed> $data
     */
    public function convert(
        array $data,
        Context $context,
        MigrationContextInterface $migrationContext
    ): ConvertStruct {
        $this->generateChecksum($data);
        $this->context = $context;
        $this->runId = $migrationContext->getRunUuid();

        $connection = $migrationContext->getConnection();
        if ($connection === null) {
            return new ConvertStruct(null, $data);
        }

        $this->connectionId = $connection->getId();

        // Validate required fields
        if (empty($data['id']) || empty($data['email'])) {
            return new ConvertStruct(null, $data);
        }

        // Create or get mapping for this customer
        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            CustomerDataSet::getEntity(),
            $data['id'],
            $context,
            $this->checksum
        );

        $converted = [];
        $converted['id'] = $this->mainMapping['entityUuid'];

        // Map fields from 6.4.20 to 6.7 structure
        $converted = $this->convertBasicFields($converted, $data);
        $converted = $this->convertAssociations($converted, $data);
        $converted = $this->convertAddresses($converted, $data);
        $converted = $this->convertCustomFields($converted, $data);

        // Remove processed fields from source data
        $unmappedData = $this->getUnmappedData($data);

        $this->updateMainMapping($migrationContext, $context);

        return new ConvertStruct(
            $converted,
            empty($unmappedData) ? null : $unmappedData,
            $this->mainMapping['id']
        );
    }

    /**
     * Convert basic customer fields
     *
     * @param array<string, mixed> $converted
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function convertBasicFields(array $converted, array $data): array
    {
        // Required fields
        $converted['email'] = (string) $data['email'];
        $converted['firstName'] = (string) ($data['first_name'] ?? '');
        $converted['lastName'] = (string) ($data['last_name'] ?? '');

        // Customer number
        if (!empty($data['customer_number'])) {
            $converted['customerNumber'] = (string) $data['customer_number'];
        }

        // Optional string fields
        if (!empty($data['company'])) {
            $converted['company'] = (string) $data['company'];
        }

        if (!empty($data['title'])) {
            $converted['title'] = (string) $data['title'];
        }

        // Boolean fields
        $converted['active'] = (bool) ($data['active'] ?? true);
        $converted['guest'] = (bool) ($data['guest'] ?? false);
        $converted['newsletter'] = (bool) ($data['newsletter'] ?? false);

        // Date fields
        if (!empty($data['birthday'])) {
            $converted['birthday'] = $data['birthday'];
        }

        if (!empty($data['first_login'])) {
            $converted['firstLogin'] = $data['first_login'];
        }

        if (!empty($data['last_login'])) {
            $converted['lastLogin'] = $data['last_login'];
        }

        if (!empty($data['last_order_date'])) {
            $converted['lastOrderDate'] = $data['last_order_date'];
        }

        // Order statistics
        if (isset($data['order_count'])) {
            $converted['orderCount'] = (int) $data['order_count'];
        }

        if (isset($data['order_total_amount'])) {
            $converted['orderTotalAmount'] = (float) $data['order_total_amount'];
        }

        return $converted;
    }

    /**
     * Convert association IDs
     *
     * @param array<string, mixed> $converted
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function convertAssociations(array $converted, array $data): array
    {
        // Customer Group
        if (!empty($data['customer_group_id'])) {
            $groupMapping = $this->mappingService->getMapping(
                $this->connectionId,
                'customer_group',
                $data['customer_group_id'],
                $this->context
            );

            if ($groupMapping !== null) {
                $converted['groupId'] = $groupMapping['entityUuid'];
                $this->mappingIds[] = $groupMapping['id'];
            } else {
                // Use same ID if exists in target
                $converted['groupId'] = $data['customer_group_id'];
            }
        }

        // Default Payment Method
        if (!empty($data['default_payment_method_id'])) {
            $paymentMapping = $this->mappingService->getMapping(
                $this->connectionId,
                'payment_method',
                $data['default_payment_method_id'],
                $this->context
            );

            if ($paymentMapping !== null) {
                $converted['defaultPaymentMethodId'] = $paymentMapping['entityUuid'];
                $this->mappingIds[] = $paymentMapping['id'];
            } else {
                $converted['defaultPaymentMethodId'] = $data['default_payment_method_id'];
            }
        }

        // Sales Channel
        if (!empty($data['sales_channel_id'])) {
            $salesChannelMapping = $this->mappingService->getMapping(
                $this->connectionId,
                'sales_channel',
                $data['sales_channel_id'],
                $this->context
            );

            if ($salesChannelMapping !== null) {
                $converted['salesChannelId'] = $salesChannelMapping['entityUuid'];
                $this->mappingIds[] = $salesChannelMapping['id'];
            } else {
                $converted['salesChannelId'] = $data['sales_channel_id'];
            }
        }

        // Language
        if (!empty($data['language_id'])) {
            $languageMapping = $this->mappingService->getMapping(
                $this->connectionId,
                'language',
                $data['language_id'],
                $this->context
            );

            if ($languageMapping !== null) {
                $converted['languageId'] = $languageMapping['entityUuid'];
                $this->mappingIds[] = $languageMapping['id'];
            } else {
                $converted['languageId'] = $data['language_id'];
            }
        }

        // Salutation
        if (!empty($data['salutation_id'])) {
            $converted['salutationId'] = $data['salutation_id'];
        }

        return $converted;
    }

    /**
     * Convert customer addresses
     *
     * @param array<string, mixed> $converted
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function convertAddresses(array $converted, array $data): array
    {
        if (empty($data['addresses']) || !is_array($data['addresses'])) {
            return $converted;
        }

        $addresses = [];
        $defaultBillingId = $data['default_billing_address_id'] ?? null;
        $defaultShippingId = $data['default_shipping_address_id'] ?? null;

        foreach ($data['addresses'] as $address) {
            $addressMapping = $this->mappingService->getOrCreateMapping(
                $this->connectionId,
                'customer_address',
                $address['id'],
                $this->context
            );

            $convertedAddress = [
                'id' => $addressMapping['entityUuid'],
                'firstName' => (string) ($address['first_name'] ?? ''),
                'lastName' => (string) ($address['last_name'] ?? ''),
                'street' => (string) ($address['street'] ?? ''),
                'zipcode' => (string) ($address['zipcode'] ?? ''),
                'city' => (string) ($address['city'] ?? ''),
            ];

            // Optional fields
            if (!empty($address['company'])) {
                $convertedAddress['company'] = (string) $address['company'];
            }

            if (!empty($address['department'])) {
                $convertedAddress['department'] = (string) $address['department'];
            }

            if (!empty($address['title'])) {
                $convertedAddress['title'] = (string) $address['title'];
            }

            if (!empty($address['phone_number'])) {
                $convertedAddress['phoneNumber'] = (string) $address['phone_number'];
            }

            if (!empty($address['additional_address_line1'])) {
                $convertedAddress['additionalAddressLine1'] = (string) $address['additional_address_line1'];
            }

            if (!empty($address['additional_address_line2'])) {
                $convertedAddress['additionalAddressLine2'] = (string) $address['additional_address_line2'];
            }

            // Country
            if (!empty($address['country_id'])) {
                $countryMapping = $this->mappingService->getMapping(
                    $this->connectionId,
                    'country',
                    $address['country_id'],
                    $this->context
                );

                if ($countryMapping !== null) {
                    $convertedAddress['countryId'] = $countryMapping['entityUuid'];
                    $this->mappingIds[] = $countryMapping['id'];
                } else {
                    $convertedAddress['countryId'] = $address['country_id'];
                }
            }

            // Country State
            if (!empty($address['country_state_id'])) {
                $stateMapping = $this->mappingService->getMapping(
                    $this->connectionId,
                    'country_state',
                    $address['country_state_id'],
                    $this->context
                );

                if ($stateMapping !== null) {
                    $convertedAddress['countryStateId'] = $stateMapping['entityUuid'];
                    $this->mappingIds[] = $stateMapping['id'];
                } else {
                    $convertedAddress['countryStateId'] = $address['country_state_id'];
                }
            }

            // Salutation
            if (!empty($address['salutation_id'])) {
                $convertedAddress['salutationId'] = $address['salutation_id'];
            }

            // Custom fields
            if (!empty($address['custom_fields'])) {
                $customFields = $address['custom_fields'];
                if (is_string($customFields)) {
                    $customFields = json_decode($customFields, true);
                }
                if (is_array($customFields) && !empty($customFields)) {
                    $convertedAddress['customFields'] = $customFields;
                }
            }

            $addresses[] = $convertedAddress;

            // Track mapping
            $this->mappingIds[] = $addressMapping['id'];

            // Set default addresses
            if ($address['id'] === $defaultBillingId) {
                $converted['defaultBillingAddressId'] = $addressMapping['entityUuid'];
            }

            if ($address['id'] === $defaultShippingId) {
                $converted['defaultShippingAddressId'] = $addressMapping['entityUuid'];
            }
        }

        if (!empty($addresses)) {
            $converted['addresses'] = $addresses;
        }

        return $converted;
    }

    /**
     * Convert custom fields
     *
     * @param array<string, mixed> $converted
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function convertCustomFields(array $converted, array $data): array
    {
        if (empty($data['custom_fields'])) {
            return $converted;
        }

        $customFields = $data['custom_fields'];

        if (is_string($customFields)) {
            $customFields = json_decode($customFields, true);
        }

        if (is_array($customFields) && !empty($customFields)) {
            $converted['customFields'] = $customFields;
        }

        return $converted;
    }

    /**
     * Get unmapped data that wasn't processed
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function getUnmappedData(array $data): array
    {
        $processedFields = [
            'id',
            'customer_group_id',
            'default_payment_method_id',
            'sales_channel_id',
            'language_id',
            'default_billing_address_id',
            'default_shipping_address_id',
            'customer_number',
            'salutation_id',
            'first_name',
            'last_name',
            'company',
            'email',
            'title',
            'active',
            'guest',
            'first_login',
            'last_login',
            'newsletter',
            'birthday',
            'last_order_date',
            'order_count',
            'order_total_amount',
            'custom_fields',
            'created_at',
            'updated_at',
            'addresses',
        ];

        return array_diff_key($data, array_flip($processedFields));
    }
}
