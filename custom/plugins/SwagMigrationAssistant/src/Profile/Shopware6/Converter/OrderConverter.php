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
use SwagMigrationAssistant\Migration\Mapping\Lookup\StateMachineStateLookup;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\OrderDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('fundamentals@after-sales')]
class OrderConverter extends ShopwareConverter
{
    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        private readonly StateMachineStateLookup $stateMachineStateLookup,
    ) {
        parent::__construct($mappingService, $loggingService);
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware6MajorProfile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === OrderDataSet::getEntity();
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::ORDER,
            $data['id'],
            $converted['id']
        );

        $converted['currencyId'] = $this->getMappingIdFacade(
            DefaultEntities::CURRENCY,
            $converted['currencyId']
        );

        $converted['languageId'] = $this->getMappingIdFacade(
            DefaultEntities::LANGUAGE,
            $converted['languageId']
        );

        $converted['salesChannelId'] = $this->getMappingIdFacade(
            DefaultEntities::SALES_CHANNEL,
            $converted['salesChannelId']
        );

        $converted['orderCustomer']['salutationId'] = $this->getMappingIdFacade(
            DefaultEntities::SALUTATION,
            $converted['orderCustomer']['salutationId']
        );

        $converted['stateId'] = $this->stateMachineStateLookup->get(
            $converted['stateMachineState']['technicalName'],
            $converted['stateMachineState']['stateMachine']['technicalName'],
            $this->context
        );

        unset($converted['stateMachineState']);

        foreach ($converted['deliveries'] as &$delivery) {
            $delivery['stateId'] = $this->stateMachineStateLookup->get(
                $delivery['stateMachineState']['technicalName'],
                $delivery['stateMachineState']['stateMachine']['technicalName'],
                $this->context
            );

            unset($delivery['stateMachineState']);

            if (isset($delivery['shippingOrderAddress']['countryStateId'])) {
                $delivery['shippingOrderAddress']['countryStateId'] = $this->getMappingIdFacade(DefaultEntities::COUNTRY_STATE, $delivery['shippingOrderAddress']['countryStateId']);
            }

            $delivery['shippingOrderAddress']['countryId'] = $this->getMappingIdFacade(DefaultEntities::COUNTRY, $delivery['shippingOrderAddress']['countryId']);
            $delivery['shippingOrderAddress']['salutationId'] = $this->getMappingIdFacade(DefaultEntities::SALUTATION, $delivery['shippingOrderAddress']['salutationId']);
        }
        unset($delivery);

        foreach ($converted['transactions'] as &$transaction) {
            $transaction['stateId'] = $this->stateMachineStateLookup->get(
                $transaction['stateMachineState']['technicalName'],
                $transaction['stateMachineState']['stateMachine']['technicalName'],
                $this->context
            );

            unset($transaction['stateMachineState']);
        }
        unset($transaction);

        $this->updateAssociationIds(
            $converted['transactions'],
            DefaultEntities::PAYMENT_METHOD,
            'paymentMethodId',
            DefaultEntities::ORDER
        );

        $this->updateAssociationIds(
            $converted['addresses'],
            DefaultEntities::COUNTRY,
            'countryId',
            DefaultEntities::ORDER
        );

        $this->updateAssociationIds(
            $converted['addresses'],
            DefaultEntities::COUNTRY_STATE,
            'countryStateId',
            DefaultEntities::ORDER
        );

        $this->updateAssociationIds(
            $converted['addresses'],
            DefaultEntities::SALUTATION,
            'salutationId',
            DefaultEntities::ORDER
        );

        $this->updateAssociationIds(
            $converted['lineItems'],
            DefaultEntities::PRODUCT,
            'productId',
            DefaultEntities::ORDER,
            false,
            true
        );

        $this->updateAssociationIds(
            $converted['lineItems'],
            DefaultEntities::MEDIA,
            'coverId',
            DefaultEntities::ORDER,
            false,
            true
        );

        $this->updateLineItems($converted['lineItems']);

        if (!isset($converted['price']['rawTotal']) && isset($converted['price']['totalPrice'])) {
            $converted['price']['rawTotal'] = $converted['price']['totalPrice'];
        }

        if (isset($data['createdById'])) {
            $converted['createdById'] = $this->getMappingIdFacade(DefaultEntities::USER, $data['createdById']);
        }

        if (isset($data['updatedById'])) {
            $converted['updatedById'] = $this->getMappingIdFacade(DefaultEntities::USER, $data['updatedById']);
        }

        // Fix empty street/city in order addresses (required fields in SW 6.7)
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

        // Fix empty street/city in delivery shipping addresses
        if (isset($converted['deliveries']) && is_array($converted['deliveries'])) {
            foreach ($converted['deliveries'] as &$delivery) {
                if (isset($delivery['shippingOrderAddress'])) {
                    if (!isset($delivery['shippingOrderAddress']['street']) || trim((string) $delivery['shippingOrderAddress']['street']) === '') {
                        $delivery['shippingOrderAddress']['street'] = '-';
                    }
                    if (!isset($delivery['shippingOrderAddress']['city']) || trim((string) $delivery['shippingOrderAddress']['city']) === '') {
                        $delivery['shippingOrderAddress']['city'] = '-';
                    }
                }
            }
            unset($delivery);
        }

        // Fix letterName in nested salutations (SW 6.7 requirement)
        $this->fixSalutationLetterName($converted);

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }

    /**
     * Removes nested salutation objects and ensures only salutationId is kept.
     * This prevents DAL nested writes that fail due to missing letterName in SW 6.7.
     * The salutation entity should already exist from the salutation migration step.
     *
     * @param array<string, mixed> $converted
     */
    private function fixSalutationLetterName(array &$converted): void
    {
        // Remove orderCustomer salutation object, keep only salutationId
        if (isset($converted['orderCustomer']['salutation'])) {
            unset($converted['orderCustomer']['salutation']);
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

        // Remove salutation objects from deliveries, keep only salutationId
        if (isset($converted['deliveries']) && is_array($converted['deliveries'])) {
            foreach ($converted['deliveries'] as &$delivery) {
                if (isset($delivery['shippingOrderAddress']['salutation'])) {
                    unset($delivery['shippingOrderAddress']['salutation']);
                }
            }
            unset($delivery);
        }

        // Remove salutation from billingAddress if present
        if (isset($converted['billingAddress']['salutation'])) {
            unset($converted['billingAddress']['salutation']);
        }
    }

    /**
     * @param array<mixed> $lineItems
     */
    private function updateLineItems(array &$lineItems): void
    {
        foreach ($lineItems as &$converted) {
            if (!isset($converted['productId'])) {
                unset($converted['referencedId'], $converted['payload']['productNumber']);
            }

            // Clear promotionId if not mapped (prevents FK violation)
            if (isset($converted['promotionId'])) {
                $promotionId = $this->getMappingIdFacade(
                    DefaultEntities::PROMOTION,
                    $converted['promotionId']
                );
                if ($promotionId === null) {
                    unset($converted['promotionId']);
                } else {
                    $converted['promotionId'] = $promotionId;
                }
            }

            if (!isset($converted['payload'])) {
                continue;
            }

            if (isset($converted['payload']['taxId'])) {
                $taxId = $this->getMappingIdFacade(
                    DefaultEntities::TAX,
                    $converted['payload']['taxId']
                );

                if ($taxId !== null) {
                    $converted['payload']['taxId'] = $taxId;
                }
            }
        }
    }
}
