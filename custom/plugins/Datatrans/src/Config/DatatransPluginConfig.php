<?php declare(strict_types=1);

namespace Allquanto\Datatrans\Config;


class DatatransPluginConfig
{
    /**
     * @var array
     */
    private $rawConfig;

    /**
     * @var bool
     */
    private $inherited;

    public function __construct(array $rawConfig, bool $inherited = true)
    {
        $this->rawConfig = $rawConfig;
        $this->inherited = $inherited;
    }

    public function isInherited(): bool
    {
        return $this->inherited;
    }

    public function getMerchantId(): ?string
    {
        return $this->getConfigValueOrNull('datatransMerchantId');
    }

    public function getPassword(): ?string
    {
        return $this->getConfigValueOrNull('datatransPassword');
    }

    public function getBasicAuth(): ?array
    {
        return [$this->getMerchantId(),  $this->getPassword()];
    }

    public function getSing(): ?string
    {
        return $this->getConfigValueOrNull('datatransSign');
    }

    public function getSing2(): ?string
    {
        return $this->getConfigValueOrNull('datatransSign2');
    }

    public function getRedirectUrl(): ?string
    {
        return $this->getConfigValueOrNull('datatransRedirectUrl');
    }

    public function getApiBaseUrl(): ?string
    {
        return $this->getConfigValueOrNull('datatransApiBaseUrl');
    }

    public function getWebhookBaseUrl(): ?string
    {
        return $this->getConfigValueOrNull('datatransWebhookBaseUrl');
    }

    public function getCardPaymentType(): ?string
    {
        return $this->getConfigValueOrNull('datatransCardPaymentType');
    }

    public function getCardBrands(): ?array
    {
        return $this->getConfigValueOrNull('datatransCardBrands');
    }

    public function getLipoCardPaymentType(): ?string
    {
        return $this->getConfigValueOrNull('datatransLipoCardPaymentType');
    }

    public function getLipoCardBrands(): ?array
    {
        return $this->getConfigValueOrNull('datatransLipoCardBrands');
    }

    public function getPostFinancePaymentType(): ?string
    {
        return $this->getConfigValueOrNull('datatransPostFinancePaymentType');
    }

    public function getPostFinanceCodes(): ?array
    {
        return $this->getConfigValueOrNull('datatransPostFinanceCodes');
    }

    public function getTwintPaymentType(): ?string
    {
        return $this->getConfigValueOrNull('datatransTwintPaymentType');
    }

    public function getTwintCodes(): ?array
    {
        return $this->getConfigValueOrNull('datatransTwintCodes');
    }

    public function getPayPalPaymentType(): ?string
    {
        return $this->getConfigValueOrNull('datatransPayPalPaymentType');
    }

    public function getPayPalCodes(): ?array
    {
        return $this->getConfigValueOrNull('datatransPayPalCodes');
    }

    public function getPowerpayPaymentType(): ?string
    {
        return $this->getConfigValueOrNull('datatransPowerpayPaymentType');
    }

    public function getPowerpayCodes(): ?array
    {
        return $this->getConfigValueOrNull('datatransPowerpayCodes');
    }

    public function getCembrapayPaymentType(): ?string
    {
        return $this->getConfigValueOrNull('datatransCembrapayPaymentType');
    }

    public function getCembrapayCodes(): ?array
    {
        return $this->getConfigValueOrNull('datatransCembrapayCodes');
    }

    public function getRawConfig(): array
    {
        return $this->rawConfig;
    }

    private function getConfigValueOrNull(string $configKey)
    {
        return $this->rawConfig[$configKey] ?? null;
    }
}
