<?php declare(strict_types=1);

namespace Allquanto\Datatrans\Installation;

use Allquanto\Datatrans\DatatransPayment;
use Allquanto\Datatrans\PaymentMethods;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\System\Language\LanguageEntity;

#[Package('checkout')]
class DatatransPaymentInstaller
{
    public function __construct(
        private readonly Context $context,
        private readonly PluginIdProvider $pluginIdProvider,
        private readonly EntityRepository $paymentMethodRepository,
        private readonly EntityRepository $languageRepository
    ) {
    }

    public function install(): void
    {
        $this->update();
    }

    public function update(): void
    {
        $this->ensurePaymentMethods();
    }

    public function activate(): void
    {
        $this->setAllPaymentMethodsState(true);
    }

    public function deactivate(): void
    {
        $this->setAllPaymentMethodsState(false);
    }

    private function setAllPaymentMethodsState(bool $status): void
    {
        foreach (PaymentMethods\PaymentMethods::PAYMENT_METHODS as $paymentMethod) {
            $pm = new $paymentMethod();
            $paymentMethodHandlerIdentifier = $pm->getPaymentHandler();
            $paymentMethodId = $this->getPaymentMethodIdForHandlerIdentifier($paymentMethodHandlerIdentifier);

            if ($paymentMethodId === null) {
                continue;
            }

            $this->paymentMethodRepository->update([
                [
                    'id' => $paymentMethodId,
                    'active' => $status,
                ],
            ], $this->context);
        }
    }

    private function ensurePaymentMethods(): void
    {
        $defaultLocaleCode = $this->getSystemDefaultLocaleCode();

        foreach (PaymentMethods\PaymentMethods::PAYMENT_METHODS as $paymentMethod) {
            $pm = new $paymentMethod();
            $paymentMethodHandlerIdentifier = $pm->getPaymentHandler();
            $paymentMethodId = $this->getPaymentMethodIdForHandlerIdentifier($paymentMethodHandlerIdentifier);
            $translations = $pm->getTranslations();

            if ($defaultLocaleCode !== null && !isset($translations[$defaultLocaleCode])) {
                $translations[$defaultLocaleCode] = $translations['en-GB'];
            }

            // Generate technicalName from handler class name (required in Shopware 6.7)
            $technicalName = 'payment_' . strtolower(
                preg_replace(
                    '/([a-z])([A-Z])/',
                    '$1_$2',
                    str_replace(
                        ['Handler', 'PaymentMethod'],
                        '',
                        (new \ReflectionClass($paymentMethodHandlerIdentifier))->getShortName()
                    )
                )
            );

            $this->paymentMethodRepository->upsert([
                [
                    'id' => $paymentMethodId,
                    'handlerIdentifier' => $paymentMethodHandlerIdentifier,
                    'technicalName' => $technicalName,
                    'pluginId' => $this->getPluginId(),
                    'translations' => $translations,
                ],
            ], $this->context);
        }
    }

    private function getPaymentMethodIdForHandlerIdentifier(string $paymentMethodHandlerIdentifier): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('handlerIdentifier', $paymentMethodHandlerIdentifier));

        return $this->paymentMethodRepository
            ->searchIds($criteria, $this->context)
            ->firstId();
    }

    private function getPluginId(): string
    {
        return $this->pluginIdProvider->getPluginIdByBaseClass(
            DatatransPayment::class,
            $this->context
        );
    }

    private function getSystemDefaultLocaleCode(): ?string
    {
        $criteria = new Criteria([Defaults::LANGUAGE_SYSTEM]);
        $criteria->addAssociation('locale');

        /** @var LanguageEntity|null $systemDefaultLanguage */
        $systemDefaultLanguage = $this->languageRepository->search($criteria, $this->context)->first();

        return $systemDefaultLanguage?->getLocale()?->getCode();
    }
}
