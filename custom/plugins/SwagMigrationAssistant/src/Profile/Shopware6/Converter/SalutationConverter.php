<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Converter;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\Lookup\SalutationLookup;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\SalutationDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('fundamentals@after-sales')]
class SalutationConverter extends ShopwareConverter
{
    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        protected readonly SalutationLookup $salutationLookup,
    ) {
        parent::__construct($mappingService, $loggingService);
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware6MajorProfile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === SalutationDataSet::getEntity();
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        $salutationMapping = $this->mappingService->getMapping($this->connectionId, DefaultEntities::SALUTATION, $data['id'], $this->context);
        if ($salutationMapping !== null) {
            $salutationUuid = $salutationMapping['entityUuid'];
        } else {
            $salutationUuid = $this->salutationLookup->get($data['salutationKey'], $this->context);
        }

        if ($salutationUuid !== null) {
            $converted['id'] = $salutationUuid;
        }

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::SALUTATION,
            $data['id'],
            $converted['id']
        );

        $this->updateAssociationIds(
            $converted['translations'],
            DefaultEntities::LANGUAGE,
            'languageId',
            DefaultEntities::SALUTATION
        );

        // letterName is required non-null in SW 6.7 - generate fallback if missing
        // Ultimate fallback pattern to guarantee non-empty letterName
        if (isset($converted['translations']) && is_array($converted['translations'])) {
            foreach ($converted['translations'] as &$translation) {
                $letterName = $translation['letterName'] ?? null;

                // Check if letterName is empty, null, or whitespace-only
                if ($letterName === null || !is_string($letterName) || trim($letterName) === '') {
                    // Fallback 1: Use displayName from same translation
                    $fallback = $translation['displayName'] ?? null;

                    // Fallback 2: Use salutationKey from source data
                    if ($fallback === null || !is_string($fallback) || trim($fallback) === '') {
                        $fallback = $data['salutationKey'] ?? null;
                    }

                    // Fallback 3: Use converted salutationKey
                    if ($fallback === null || !is_string($fallback) || trim($fallback) === '') {
                        $fallback = $converted['salutationKey'] ?? null;
                    }

                    // Ultimate fallback: Generic greeting
                    if ($fallback === null || !is_string($fallback) || trim($fallback) === '') {
                        $fallback = 'Dear Sir/Madam';
                    }

                    $translation['letterName'] = trim($fallback);
                }
            }
            unset($translation);
        }

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
