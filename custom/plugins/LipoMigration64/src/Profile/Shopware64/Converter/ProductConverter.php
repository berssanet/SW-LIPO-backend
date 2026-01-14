<?php declare(strict_types=1);

namespace LipoMigration64\Profile\Shopware64\Converter;

use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\Converter\Converter;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use LipoMigration64\Profile\Shopware64\DataSelection\DataSet\ProductDataSet;
use LipoMigration64\Profile\Shopware64\Shopware64Profile;

class ProductConverter extends Converter
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
            && $migrationContext->getDataSet()::getEntity() === ProductDataSet::getEntity();
    }

    public function getSourceIdentifier(array $data): string
    {
        return $data['id'];
    }

    /**
     * Converts Shopware 6.4.20 product data to 6.7 structure
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
        if (empty($data['id']) || empty($data['product_number'])) {
            return new ConvertStruct(null, $data);
        }

        // Create or get mapping for this product
        $this->mainMapping = $this->mappingService->getOrCreateMapping(
            $this->connectionId,
            ProductDataSet::getEntity(),
            $data['id'],
            $context,
            $this->checksum
        );

        $converted = [];
        $converted['id'] = $this->mainMapping['entityUuid'];

        // Map fields from 6.4.20 to 6.7 structure
        $converted = $this->convertBasicFields($converted, $data);
        $converted = $this->convertPrice($converted, $data);
        $converted = $this->convertTranslations($converted, $data);
        $converted = $this->convertAssociations($converted, $data);

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
     * Convert basic product fields with type casting
     *
     * @param array<string, mixed> $converted
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function convertBasicFields(array $converted, array $data): array
    {
        // Direct field mappings (same in 6.4 and 6.7)
        $converted['productNumber'] = (string) $data['product_number'];
        $converted['stock'] = (int) ($data['stock'] ?? 0);
        $converted['availableStock'] = (int) ($data['available_stock'] ?? $converted['stock']);
        $converted['active'] = (bool) ($data['active'] ?? false);

        // Optional numeric fields
        if (isset($data['purchase_unit']) && $data['purchase_unit'] !== null) {
            $converted['purchaseUnit'] = (float) $data['purchase_unit'];
        }

        if (isset($data['reference_unit']) && $data['reference_unit'] !== null) {
            $converted['referenceUnit'] = (float) $data['reference_unit'];
        }

        // Physical dimensions (stored in mm in 6.7)
        if (isset($data['weight']) && $data['weight'] !== null) {
            $converted['weight'] = (float) $data['weight'];
        }

        if (isset($data['width']) && $data['width'] !== null) {
            $converted['width'] = (float) $data['width'];
        }

        if (isset($data['height']) && $data['height'] !== null) {
            $converted['height'] = (float) $data['height'];
        }

        if (isset($data['length']) && $data['length'] !== null) {
            $converted['length'] = (float) $data['length'];
        }

        // Purchase constraints
        $converted['minPurchase'] = (int) ($data['min_purchase'] ?? 1);

        if (isset($data['max_purchase']) && $data['max_purchase'] !== null) {
            $converted['maxPurchase'] = (int) $data['max_purchase'];
        }

        $converted['purchaseSteps'] = (int) ($data['purchase_steps'] ?? 1);

        // Boolean flags
        $converted['isCloseout'] = (bool) ($data['is_closeout'] ?? false);
        $converted['shippingFree'] = (bool) ($data['shipping_free'] ?? false);
        $converted['markAsTopseller'] = (bool) ($data['mark_as_topseller'] ?? false);

        return $converted;
    }

    /**
     * Convert price field from JSON to array
     *
     * @param array<string, mixed> $converted
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function convertPrice(array $converted, array $data): array
    {
        if (empty($data['price'])) {
            return $converted;
        }

        $priceData = $data['price'];

        // Price is stored as JSON in 6.4.20
        if (is_string($priceData)) {
            $priceData = json_decode($priceData, true);
        }

        if (!is_array($priceData) || empty($priceData)) {
            return $converted;
        }

        // In 6.7, price structure remains compatible
        $converted['price'] = $priceData;

        return $converted;
    }

    /**
     * Convert translation fields
     *
     * @param array<string, mixed> $converted
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function convertTranslations(array $converted, array $data): array
    {
        // Build translation array for default language
        if (isset($data['name']) && !empty($data['name'])) {
            $converted['name'] = (string) $data['name'];
        }

        if (isset($data['description']) && $data['description'] !== null) {
            $converted['description'] = (string) $data['description'];
        }

        if (isset($data['meta_title']) && $data['meta_title'] !== null) {
            $converted['metaTitle'] = (string) $data['meta_title'];
        }

        if (isset($data['meta_description']) && $data['meta_description'] !== null) {
            $converted['metaDescription'] = (string) $data['meta_description'];
        }

        if (isset($data['keywords']) && $data['keywords'] !== null) {
            $converted['keywords'] = (string) $data['keywords'];
        }

        // Custom search keywords (new in later versions)
        if (isset($data['custom_search_keywords']) && $data['custom_search_keywords'] !== null) {
            $keywords = $data['custom_search_keywords'];
            if (is_string($keywords)) {
                $keywords = json_decode($keywords, true);
            }
            if (is_array($keywords)) {
                $converted['customSearchKeywords'] = $keywords;
            }
        }

        return $converted;
    }

    /**
     * Convert association IDs (tax, manufacturer, unit, parent)
     *
     * @param array<string, mixed> $converted
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function convertAssociations(array $converted, array $data): array
    {
        // Tax ID mapping
        if (!empty($data['tax_id'])) {
            $taxMapping = $this->mappingService->getMapping(
                $this->connectionId,
                'tax',
                $data['tax_id'],
                $this->context
            );

            if ($taxMapping !== null) {
                $converted['taxId'] = $taxMapping['entityUuid'];
                $this->mappingIds[] = $taxMapping['id'];
            } else {
                // Use same ID if tax exists in target (common for 6.4 -> 6.7)
                $converted['taxId'] = $data['tax_id'];
            }
        }

        // Manufacturer ID
        if (!empty($data['manufacturer_id'])) {
            $manufacturerMapping = $this->mappingService->getMapping(
                $this->connectionId,
                'product_manufacturer',
                $data['manufacturer_id'],
                $this->context
            );

            if ($manufacturerMapping !== null) {
                $converted['manufacturerId'] = $manufacturerMapping['entityUuid'];
                $this->mappingIds[] = $manufacturerMapping['id'];
            } else {
                $converted['manufacturerId'] = $data['manufacturer_id'];
            }
        }

        // Unit ID
        if (!empty($data['unit_id'])) {
            $converted['unitId'] = $data['unit_id'];
        }

        // Parent ID for variants
        if (!empty($data['parent_id'])) {
            $parentMapping = $this->mappingService->getMapping(
                $this->connectionId,
                ProductDataSet::getEntity(),
                $data['parent_id'],
                $this->context
            );

            if ($parentMapping !== null) {
                $converted['parentId'] = $parentMapping['entityUuid'];
                $this->mappingIds[] = $parentMapping['id'];
            } else {
                $converted['parentId'] = $data['parent_id'];
            }
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
        // Fields we've already processed
        $processedFields = [
            'id',
            'version_id',
            'parent_id',
            'product_number',
            'stock',
            'available_stock',
            'active',
            'price',
            'tax_id',
            'manufacturer_id',
            'unit_id',
            'cover_id',
            'purchase_unit',
            'reference_unit',
            'weight',
            'width',
            'height',
            'length',
            'min_purchase',
            'max_purchase',
            'purchase_steps',
            'is_closeout',
            'shipping_free',
            'mark_as_topseller',
            'created_at',
            'updated_at',
            'name',
            'description',
            'meta_title',
            'meta_description',
            'keywords',
            'custom_search_keywords',
        ];

        return array_diff_key($data, array_flip($processedFields));
    }
}
