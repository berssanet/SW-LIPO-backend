<?php declare(strict_types=1);

namespace LipoMigration64\Tests\Unit\Profile\Shopware64\Converter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Shopware\Core\Framework\Context;
use SwagMigrationAssistant\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use LipoMigration64\Profile\Shopware64\Converter\ProductConverter;
use LipoMigration64\Profile\Shopware64\DataSelection\DataSet\ProductDataSet;
use LipoMigration64\Profile\Shopware64\Shopware64Profile;

#[CoversClass(ProductConverter::class)]
final class ProductConverterTest extends TestCase
{
    private ProductConverter $converter;
    private MappingServiceInterface&MockObject $mappingService;
    private LoggingServiceInterface&MockObject $loggingService;
    private MigrationContextInterface&MockObject $migrationContext;
    private Context $context;

    protected function setUp(): void
    {
        $this->mappingService = $this->createMock(MappingServiceInterface::class);
        $this->loggingService = $this->createMock(LoggingServiceInterface::class);
        $this->converter = new ProductConverter($this->mappingService, $this->loggingService);

        $this->context = Context::createDefaultContext();

        $connection = $this->createMock(SwagMigrationConnectionEntity::class);
        $connection->method('getId')->willReturn('test-connection-id');

        $profile = new Shopware64Profile();
        $dataSet = new ProductDataSet();

        $this->migrationContext = $this->createMock(MigrationContextInterface::class);
        $this->migrationContext->method('getConnection')->willReturn($connection);
        $this->migrationContext->method('getProfile')->willReturn($profile);
        $this->migrationContext->method('getDataSet')->willReturn($dataSet);
        $this->migrationContext->method('getRunUuid')->willReturn('test-run-uuid');
    }

    public function testSupportsReturnsTrueForShopware64Profile(): void
    {
        $this->assertTrue($this->converter->supports($this->migrationContext));
    }

    public function testConvertReturnsNullForMissingId(): void
    {
        $data = [
            'id' => '',
            'product_number' => 'SW10001',
        ];

        $result = $this->converter->convert($data, $this->context, $this->migrationContext);

        $this->assertNull($result->getConverted());
        $this->assertSame($data, $result->getUnmapped());
    }

    public function testConvertReturnsNullForMissingProductNumber(): void
    {
        $data = [
            'id' => 'abc123',
            'product_number' => '',
        ];

        $result = $this->converter->convert($data, $this->context, $this->migrationContext);

        $this->assertNull($result->getConverted());
        $this->assertSame($data, $result->getUnmapped());
    }

    public function testConvertBasicFieldsCorrectly(): void
    {
        $data = $this->getValidProductData();

        $this->mappingService
            ->method('getOrCreateMapping')
            ->willReturn([
                'id' => 'mapping-id',
                'entityUuid' => 'product-uuid-123',
            ]);

        $result = $this->converter->convert($data, $this->context, $this->migrationContext);
        $converted = $result->getConverted();

        $this->assertNotNull($converted);
        $this->assertSame('product-uuid-123', $converted['id']);
        $this->assertSame('SW10001', $converted['productNumber']);
        $this->assertSame(50, $converted['stock']);
        $this->assertSame(45, $converted['availableStock']);
        $this->assertTrue($converted['active']);
        $this->assertSame(1, $converted['minPurchase']);
        $this->assertSame(100, $converted['maxPurchase']);
        $this->assertSame(1, $converted['purchaseSteps']);
        $this->assertFalse($converted['isCloseout']);
        $this->assertTrue($converted['shippingFree']);
        $this->assertFalse($converted['markAsTopseller']);
    }

    public function testConvertPhysicalDimensions(): void
    {
        $data = $this->getValidProductData();
        $data['weight'] = 1.5;
        $data['width'] = 100.0;
        $data['height'] = 50.0;
        $data['length'] = 200.0;

        $this->mappingService
            ->method('getOrCreateMapping')
            ->willReturn([
                'id' => 'mapping-id',
                'entityUuid' => 'product-uuid-123',
            ]);

        $result = $this->converter->convert($data, $this->context, $this->migrationContext);
        $converted = $result->getConverted();

        $this->assertSame(1.5, $converted['weight']);
        $this->assertSame(100.0, $converted['width']);
        $this->assertSame(50.0, $converted['height']);
        $this->assertSame(200.0, $converted['length']);
    }

    public function testConvertPriceFromJsonString(): void
    {
        $data = $this->getValidProductData();
        $priceArray = [
            [
                'currencyId' => 'b7d2554b0ce847cd82f3ac9bd1c0dfca',
                'gross' => 119.0,
                'net' => 100.0,
                'linked' => true,
            ],
        ];
        $data['price'] = json_encode($priceArray);

        $this->mappingService
            ->method('getOrCreateMapping')
            ->willReturn([
                'id' => 'mapping-id',
                'entityUuid' => 'product-uuid-123',
            ]);

        $result = $this->converter->convert($data, $this->context, $this->migrationContext);
        $converted = $result->getConverted();

        $this->assertSame($priceArray, $converted['price']);
    }

    public function testConvertPriceFromArray(): void
    {
        $data = $this->getValidProductData();
        $priceArray = [
            [
                'currencyId' => 'b7d2554b0ce847cd82f3ac9bd1c0dfca',
                'gross' => 59.0,
                'net' => 50.0,
                'linked' => false,
            ],
        ];
        $data['price'] = $priceArray;

        $this->mappingService
            ->method('getOrCreateMapping')
            ->willReturn([
                'id' => 'mapping-id',
                'entityUuid' => 'product-uuid-123',
            ]);

        $result = $this->converter->convert($data, $this->context, $this->migrationContext);
        $converted = $result->getConverted();

        $this->assertSame($priceArray, $converted['price']);
    }

    public function testConvertTranslationFields(): void
    {
        $data = $this->getValidProductData();
        $data['name'] = 'Test Product Name';
        $data['description'] = 'Product description text';
        $data['meta_title'] = 'SEO Title';
        $data['meta_description'] = 'SEO Description';
        $data['keywords'] = 'keyword1, keyword2';

        $this->mappingService
            ->method('getOrCreateMapping')
            ->willReturn([
                'id' => 'mapping-id',
                'entityUuid' => 'product-uuid-123',
            ]);

        $result = $this->converter->convert($data, $this->context, $this->migrationContext);
        $converted = $result->getConverted();

        $this->assertSame('Test Product Name', $converted['name']);
        $this->assertSame('Product description text', $converted['description']);
        $this->assertSame('SEO Title', $converted['metaTitle']);
        $this->assertSame('SEO Description', $converted['metaDescription']);
        $this->assertSame('keyword1, keyword2', $converted['keywords']);
    }

    public function testConvertCustomSearchKeywordsFromJsonString(): void
    {
        $data = $this->getValidProductData();
        $data['custom_search_keywords'] = json_encode(['search1', 'search2']);

        $this->mappingService
            ->method('getOrCreateMapping')
            ->willReturn([
                'id' => 'mapping-id',
                'entityUuid' => 'product-uuid-123',
            ]);

        $result = $this->converter->convert($data, $this->context, $this->migrationContext);
        $converted = $result->getConverted();

        $this->assertSame(['search1', 'search2'], $converted['customSearchKeywords']);
    }

    public function testConvertCustomSearchKeywordsFromArray(): void
    {
        $data = $this->getValidProductData();
        $data['custom_search_keywords'] = ['keyword1', 'keyword2', 'keyword3'];

        $this->mappingService
            ->method('getOrCreateMapping')
            ->willReturn([
                'id' => 'mapping-id',
                'entityUuid' => 'product-uuid-123',
            ]);

        $result = $this->converter->convert($data, $this->context, $this->migrationContext);
        $converted = $result->getConverted();

        $this->assertSame(['keyword1', 'keyword2', 'keyword3'], $converted['customSearchKeywords']);
    }

    public function testConvertHandlesNullValues(): void
    {
        $data = $this->getValidProductData();
        $data['weight'] = null;
        $data['width'] = null;
        $data['description'] = null;
        $data['max_purchase'] = null;

        $this->mappingService
            ->method('getOrCreateMapping')
            ->willReturn([
                'id' => 'mapping-id',
                'entityUuid' => 'product-uuid-123',
            ]);

        $result = $this->converter->convert($data, $this->context, $this->migrationContext);
        $converted = $result->getConverted();

        $this->assertArrayNotHasKey('weight', $converted);
        $this->assertArrayNotHasKey('width', $converted);
        $this->assertArrayNotHasKey('description', $converted);
        $this->assertArrayNotHasKey('maxPurchase', $converted);
    }

    public function testConvertTypeCastingFromStrings(): void
    {
        $data = $this->getValidProductData();
        // Simulate string values from database
        $data['stock'] = '100';
        $data['active'] = '1';
        $data['min_purchase'] = '5';
        $data['purchase_unit'] = '1.5';
        $data['is_closeout'] = '0';

        $this->mappingService
            ->method('getOrCreateMapping')
            ->willReturn([
                'id' => 'mapping-id',
                'entityUuid' => 'product-uuid-123',
            ]);

        $result = $this->converter->convert($data, $this->context, $this->migrationContext);
        $converted = $result->getConverted();

        $this->assertSame(100, $converted['stock']);
        $this->assertTrue($converted['active']);
        $this->assertSame(5, $converted['minPurchase']);
        $this->assertSame(1.5, $converted['purchaseUnit']);
        $this->assertFalse($converted['isCloseout']);
    }

    public function testConvertDefaultsForMissingOptionalFields(): void
    {
        $data = [
            'id' => 'abc123',
            'product_number' => 'SW10001',
            // All other fields missing
        ];

        $this->mappingService
            ->method('getOrCreateMapping')
            ->willReturn([
                'id' => 'mapping-id',
                'entityUuid' => 'product-uuid-123',
            ]);

        $result = $this->converter->convert($data, $this->context, $this->migrationContext);
        $converted = $result->getConverted();

        $this->assertSame(0, $converted['stock']);
        $this->assertSame(0, $converted['availableStock']);
        $this->assertFalse($converted['active']);
        $this->assertSame(1, $converted['minPurchase']);
        $this->assertSame(1, $converted['purchaseSteps']);
        $this->assertFalse($converted['isCloseout']);
        $this->assertFalse($converted['shippingFree']);
        $this->assertFalse($converted['markAsTopseller']);
    }

    public function testGetSourceIdentifier(): void
    {
        $data = ['id' => 'abc123'];
        $this->assertSame('abc123', $this->converter->getSourceIdentifier($data));
    }

    #[DataProvider('emptyPriceDataProvider')]
    public function testConvertHandlesEmptyPrice(mixed $priceValue): void
    {
        $data = $this->getValidProductData();
        $data['price'] = $priceValue;

        $this->mappingService
            ->method('getOrCreateMapping')
            ->willReturn([
                'id' => 'mapping-id',
                'entityUuid' => 'product-uuid-123',
            ]);

        $result = $this->converter->convert($data, $this->context, $this->migrationContext);
        $converted = $result->getConverted();

        $this->assertArrayNotHasKey('price', $converted);
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function emptyPriceDataProvider(): array
    {
        return [
            'null price' => [null],
            'empty string' => [''],
            'empty array' => [[]],
            'empty json array' => ['[]'],
        ];
    }

    public function testConvertWithTaxMapping(): void
    {
        $data = $this->getValidProductData();
        $data['tax_id'] = 'tax-source-id';

        $this->mappingService
            ->method('getOrCreateMapping')
            ->willReturn([
                'id' => 'mapping-id',
                'entityUuid' => 'product-uuid-123',
            ]);

        $this->mappingService
            ->method('getMapping')
            ->willReturnCallback(function ($connectionId, $entity, $oldId, $context) {
                if ($entity === 'tax') {
                    return [
                        'id' => 'tax-mapping-id',
                        'entityUuid' => 'tax-uuid-target',
                    ];
                }
                return null;
            });

        $result = $this->converter->convert($data, $this->context, $this->migrationContext);
        $converted = $result->getConverted();

        $this->assertSame('tax-uuid-target', $converted['taxId']);
    }

    public function testConvertWithManufacturerMapping(): void
    {
        $data = $this->getValidProductData();
        $data['manufacturer_id'] = 'manu-source-id';

        $this->mappingService
            ->method('getOrCreateMapping')
            ->willReturn([
                'id' => 'mapping-id',
                'entityUuid' => 'product-uuid-123',
            ]);

        $this->mappingService
            ->method('getMapping')
            ->willReturnCallback(function ($connectionId, $entity, $oldId, $context) {
                if ($entity === 'product_manufacturer') {
                    return [
                        'id' => 'manu-mapping-id',
                        'entityUuid' => 'manu-uuid-target',
                    ];
                }
                return null;
            });

        $result = $this->converter->convert($data, $this->context, $this->migrationContext);
        $converted = $result->getConverted();

        $this->assertSame('manu-uuid-target', $converted['manufacturerId']);
    }

    public function testConvertWithParentIdMapping(): void
    {
        $data = $this->getValidProductData();
        $data['parent_id'] = 'parent-source-id';

        $this->mappingService
            ->method('getOrCreateMapping')
            ->willReturn([
                'id' => 'mapping-id',
                'entityUuid' => 'product-uuid-123',
            ]);

        $this->mappingService
            ->method('getMapping')
            ->willReturnCallback(function ($connectionId, $entity, $oldId, $context) {
                if ($entity === 'product' && $oldId === 'parent-source-id') {
                    return [
                        'id' => 'parent-mapping-id',
                        'entityUuid' => 'parent-uuid-target',
                    ];
                }
                return null;
            });

        $result = $this->converter->convert($data, $this->context, $this->migrationContext);
        $converted = $result->getConverted();

        $this->assertSame('parent-uuid-target', $converted['parentId']);
    }

    public function testConvertFallsBackToOriginalIdWhenNoMapping(): void
    {
        $data = $this->getValidProductData();
        $data['tax_id'] = 'tax-source-id';
        $data['manufacturer_id'] = 'manu-source-id';

        $this->mappingService
            ->method('getOrCreateMapping')
            ->willReturn([
                'id' => 'mapping-id',
                'entityUuid' => 'product-uuid-123',
            ]);

        $this->mappingService
            ->method('getMapping')
            ->willReturn(null);

        $result = $this->converter->convert($data, $this->context, $this->migrationContext);
        $converted = $result->getConverted();

        // Falls back to original IDs when no mapping exists
        $this->assertSame('tax-source-id', $converted['taxId']);
        $this->assertSame('manu-source-id', $converted['manufacturerId']);
    }

    public function testUnmappedDataReturnsExtraFields(): void
    {
        $data = $this->getValidProductData();
        $data['custom_field_1'] = 'value1';
        $data['unknown_column'] = 'value2';

        $this->mappingService
            ->method('getOrCreateMapping')
            ->willReturn([
                'id' => 'mapping-id',
                'entityUuid' => 'product-uuid-123',
            ]);

        $result = $this->converter->convert($data, $this->context, $this->migrationContext);
        $unmapped = $result->getUnmapped();

        $this->assertIsArray($unmapped);
        $this->assertArrayHasKey('custom_field_1', $unmapped);
        $this->assertArrayHasKey('unknown_column', $unmapped);
        $this->assertSame('value1', $unmapped['custom_field_1']);
        $this->assertSame('value2', $unmapped['unknown_column']);
    }

    public function testUnmappedDataReturnsNullWhenAllFieldsProcessed(): void
    {
        $data = $this->getValidProductData();
        // Ensure no extra fields

        $this->mappingService
            ->method('getOrCreateMapping')
            ->willReturn([
                'id' => 'mapping-id',
                'entityUuid' => 'product-uuid-123',
            ]);

        $result = $this->converter->convert($data, $this->context, $this->migrationContext);
        $unmapped = $result->getUnmapped();

        $this->assertNull($unmapped);
    }

    /**
     * @return array<string, mixed>
     */
    private function getValidProductData(): array
    {
        return [
            'id' => 'a1b2c3d4e5f6',
            'version_id' => 'version-123',
            'parent_id' => null,
            'product_number' => 'SW10001',
            'stock' => 50,
            'available_stock' => 45,
            'active' => true,
            'price' => null,
            'tax_id' => 'tax-id-123',
            'manufacturer_id' => null,
            'unit_id' => null,
            'cover_id' => null,
            'purchase_unit' => null,
            'reference_unit' => null,
            'weight' => null,
            'width' => null,
            'height' => null,
            'length' => null,
            'min_purchase' => 1,
            'max_purchase' => 100,
            'purchase_steps' => 1,
            'is_closeout' => false,
            'shipping_free' => true,
            'mark_as_topseller' => false,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => null,
            'name' => null,
            'description' => null,
            'meta_title' => null,
            'meta_description' => null,
            'keywords' => null,
            'custom_search_keywords' => null,
        ];
    }
}
