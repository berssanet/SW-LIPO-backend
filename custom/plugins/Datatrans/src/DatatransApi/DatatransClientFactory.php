<?php declare(strict_types=1);

namespace Allquanto\Datatrans\DatatransApi;

use Allquanto\Datatrans\Config\DatatransPluginConfig;
use Allquanto\Datatrans\Config\DatatransPluginConfigService;
use Allquanto\Datatrans\DatatransApi\Struct\Request\InitRequestStruct;
use Allquanto\Datatrans\DatatransApi\Struct\Response\InitResponseStruct;
use Allquanto\Datatrans\DatatransApi\Struct\Response\StatusResponseStruct;
use Allquanto\Datatrans\Service\DatatransCurrencyAmountService;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Routing\RouterInterface;

#[Package('checkout')]
class DatatransClientFactory
{
    public function __construct(
        private readonly DatatransPluginConfigService $serviceConfig,
        private readonly LoggerInterface $logger,
        private readonly RouterInterface $router,
        private readonly Connection $connection
    ) {
    }

    public function status(string $salesChannelId, string $datatransTransactionId): StatusResponseStruct
    {
        $config = $this->serviceConfig->getDatatransPluginConfigForSalesChannel($salesChannelId);

        $response = $this->getDatatransClient($config)->sendGet(
            \sprintf('%s/%s', EndpointV1::STATUS, $datatransTransactionId)
        );

        return (new StatusResponseStruct())->assign($response);
    }

    public function createFromOrder(
        OrderEntity $order,
        array $methods,
        string $returnUrl
    ): InitResponseStruct {
        $config = $this->serviceConfig->getDatatransPluginConfigForSalesChannel($order->getSalesChannelId());

        $response = $this->getDatatransClient($config)->sendPost(
            EndpointV1::INIT,
            $this->getDatatransInitDataFromOrder(
                $config->{$methods['codes']}(),
                $order,
                $returnUrl,
                $config->getWebhookBaseUrl()
            )->jsonSerialize()
        );

        $initResponse = new InitResponseStruct();
        $initResponse->setTransactionId($response['transactionId']);
        $initResponse->setRedirectUrl(
            $this->getRedirectUrl(
                $config->getRedirectUrl(),
                $config->{$methods['view']}(),
                $response['transactionId']
            )
        );

        return $initResponse;
    }

    private function getRedirectUrl(?string $externalUrl, ?string $view, string $datatransTransactionId): string
    {
        if ($view === 'redirect') {
            $redirectPath = \sprintf('%s/%s', EndpointV1::REDIRECT, $datatransTransactionId);

            return $externalUrl . $redirectPath;
        }

        if ($view === 'lightbox') {
            return $this->router->generate('frontend.datatrans.payment', [
                'datatransTrxId' => $datatransTransactionId,
            ]);
        }

        return '';
    }

    private function getDatatransInitDataFromOrder(
        array $codes,
        OrderEntity $order,
        string $returnUrl,
        ?string $webhookBaseUrl = null
    ): InitRequestStruct {
        $data = new InitRequestStruct();
        $data->setPaymentMethods($codes);
        $data->setAutoSettle(true);
        $data->setCurrency($order->getCurrency()?->getIsoCode());
        $data->setRefno($order->getOrderNumber());

        $data->setRedirect([
            'successUrl' => $returnUrl,
            'cancelUrl' => $returnUrl,
            'errorUrl' => $returnUrl,
            'method' => 'POST',
        ]);

        $currencyDecimals = $order->getCurrency()?->getTotalRounding()?->getDecimals() ?? 2;
        $data->setAmount(
            (new DatatransCurrencyAmountService(
                $order->getAmountTotal(),
                $currencyDecimals
            ))->getAmountInSmallestUnit()
        );

        $orderCustomer = $order->getOrderCustomer();

        if (!$orderCustomer instanceof OrderCustomerEntity) {
            throw new \RuntimeException('Order customer not found');
        }

        $billingAddress = $order->getBillingAddress();
        if ($billingAddress === null) {
            throw new \RuntimeException('Billing address not found');
        }

        $customer = $orderCustomer->getCustomer();

        $data->setCustomer([
            'id' => $orderCustomer->getId(),
            'title' => $billingAddress->getTitle(),
            'firstName' => $billingAddress->getFirstName(),
            'lastName' => $billingAddress->getLastName(),
            'street' => $billingAddress->getStreet(),
            'street2' => $billingAddress->getAdditionalAddressLine1(),
            'city' => $billingAddress->getCity(),
            'country' => $billingAddress->getCountry()?->getIso(),
            'zipCode' => $billingAddress->getZipcode(),
            'phone' => $billingAddress->getPhoneNumber(),
            'cellPhone' => $billingAddress->getPhoneNumber(),
            'email' => $orderCustomer->getEmail(),
            'gender' => $this->getCustomerGender($billingAddress->getSalutation()?->getSalutationKey()),
            'birthDate' => $customer?->getBirthday()?->format('Y-m-d'),
            'language' => $this->getLanguageCode($order->getLanguageId()),
            'type' => 'P',
            'name' => null,
            'companyLegalForm' => null,
            'companyRegisterNumber' => null,
            'ipAddress' => $orderCustomer->getRemoteAddress(),
        ]);

        $data->setBilling([
            'title' => $billingAddress->getTitle(),
            'firstName' => $billingAddress->getFirstName(),
            'lastName' => $billingAddress->getLastName(),
            'email' => $orderCustomer->getEmail(),
            'street' => $billingAddress->getStreet(),
            'street2' => $billingAddress->getAdditionalAddressLine1(),
            'zipCode' => $billingAddress->getZipcode(),
            'city' => $billingAddress->getCity(),
            'country' => $billingAddress->getCountry()?->getIso(),
            'phoneNumber' => $billingAddress->getPhoneNumber(),
        ]);

        $shippingAddress = $order->getDeliveries()?->getShippingAddress()?->first();

        if ($shippingAddress === null) {
            throw new \RuntimeException('Shipping address not found');
        }

        $data->setShipping([
            'title' => $shippingAddress->getTitle(),
            'firstName' => $shippingAddress->getFirstName(),
            'lastName' => $shippingAddress->getLastName(),
            'street' => $shippingAddress->getStreet(),
            'street2' => $shippingAddress->getAdditionalAddressLine1(),
            'city' => $shippingAddress->getCity(),
            'country' => $shippingAddress->getCountry()?->getIso(),
            'zipCode' => $shippingAddress->getZipcode(),
            'phone' => $shippingAddress->getPhoneNumber(),
            'cellPhone' => $shippingAddress->getPhoneNumber(),
        ]);

        if ($webhookBaseUrl !== null) {
            $data->setWebhook([
                'url' => $webhookBaseUrl . $this->router->generate('api.action.datatrans-payment.webhook.execute'),
            ]);
        }

        return $data;
    }

    private function getDatatransClient(DatatransPluginConfig $config): DatatransClient
    {
        return new DatatransClient(
            $this->logger,
            $config->getApiBaseUrl() ?? '',
            $config->getBasicAuth()
        );
    }

    private function getCustomerGender(?string $genderKey): ?string
    {
        if ($genderKey === null) {
            return null;
        }

        return match ($genderKey) {
            'mr' => 'male',
            'mrs' => 'female',
            default => null,
        };
    }

    private function getLanguageCode(string $id): ?string
    {
        $result = $this->connection->fetchOne(
            "SELECT IF(SUBSTRING(`locale`.code, 1, 2) = LOWER(SUBSTRING(`locale`.code, 3, 2)), 
                       UPPER(`locale`.code), 
                       LOWER(SUBSTRING(`locale`.code, 1, 2))) AS code
            FROM `language`
            LEFT JOIN `locale` ON `locale`.id = `language`.locale_id
            WHERE `language`.id = :id",
            ['id' => Uuid::fromHexToBytes($id)]
        );

        return $result !== false ? $result : null;
    }
}
