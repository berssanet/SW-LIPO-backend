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
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\CustomerDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('fundamentals@after-sales')]
class CustomerConverter extends ShopwareConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware6MajorProfile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === CustomerDataSet::getEntity();
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::CUSTOMER,
            $data['id'],
            $converted['id']
        );

        if (isset($converted['lastPaymentMethodId'])) {
            $converted['lastPaymentMethodId'] = $this->getMappingIdFacade(DefaultEntities::PAYMENT_METHOD, $converted['lastPaymentMethodId']);
        }

        if (isset($converted['defaultPaymentMethodId'])) {
            $converted['defaultPaymentMethodId'] = $this->getMappingIdFacade(DefaultEntities::PAYMENT_METHOD, $converted['defaultPaymentMethodId']);
        }

        $converted['salutationId'] = $this->getMappingIdFacade(DefaultEntities::SALUTATION, $converted['salutationId']);
        $converted['languageId'] = $this->getMappingIdFacade(DefaultEntities::LANGUAGE, $converted['languageId']);

        $this->updateAssociationIds($converted['addresses'], DefaultEntities::COUNTRY, 'countryId', DefaultEntities::CUSTOMER);
        $this->updateAssociationIds($converted['addresses'], DefaultEntities::SALUTATION, 'salutationId', DefaultEntities::CUSTOMER);
        $this->updateAssociationIds($converted['addresses'], DefaultEntities::COUNTRY_STATE, 'countryStateId', DefaultEntities::COUNTRY_STATE);

        if (isset($data['createdById'])) {
            $converted['createdById'] = $this->getMappingIdFacade(DefaultEntities::USER, $data['createdById']);
        }

        if (isset($data['updatedById'])) {
            $converted['updatedById'] = $this->getMappingIdFacade(DefaultEntities::USER, $data['updatedById']);
        }

        // Fix invalid birthday dates (e.g., '-0001-11-30', years < 1900 or > 2100)
        if (isset($converted['birthday'])) {
            $birthday = (string) $converted['birthday'];
            // Extract year from ISO format (YYYY-MM-DD)
            if (preg_match('/^(-?\d{4})/', $birthday, $matches)) {
                $year = (int) $matches[1];
                if ($year < 1900 || $year > 2100) {
                    unset($converted['birthday']);
                }
            } else {
                // Invalid format, remove it
                unset($converted['birthday']);
            }
        }

        // Fix empty street and city in addresses (required fields in SW 6.7)
        if (isset($converted['addresses']) && is_array($converted['addresses'])) {
            foreach ($converted['addresses'] as &$address) {
                if (!isset($address['street']) || trim((string) $address['street']) === '') {
                    $address['street'] = '-';
                }
                if (!isset($address['city']) || trim((string) $address['city']) === '') {
                    $address['city'] = '-';
                }
            }
            unset($address);
        }

        // Fix letterName in nested salutations (SW 6.7 requirement)
        $this->fixSalutationLetterName($converted);

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }

    /**
     * Removes nested salutation objects and ensures only salutationId is kept.
     * This prevents DAL nested writes that fail due to missing letterName in SW 6.7.
     *
     * @param array<string, mixed> $converted
     */
    private function fixSalutationLetterName(array &$converted): void
    {
        // Remove main customer salutation object, keep only salutationId
        if (isset($converted['salutation'])) {
            unset($converted['salutation']);
        }

        // Remove salutation objects from addresses, keep only salutationId
        if (isset($converted['addresses']) && is_array($converted['addresses'])) {
            foreach ($converted['addresses'] as &$address) {
                if (isset($address['salutation'])) {
                    unset($address['salutation']);
                }
            }
            unset($address);
        }
    }
}
