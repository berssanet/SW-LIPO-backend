<?php declare(strict_types=1);

namespace Allquanto\Datatrans\DatatransApi\Struct\Request;

use Shopware\Core\Framework\Struct\Struct;

class InitRequestStruct extends Struct
{
    /**
     * @var boolean|null
     */
    private ?bool $autoSettle;

    /**
     * @var string|null
     */
    private ?string $currency;

    /**
     * @var string|null
     */
    private ?string $refno;

    /**
     * @var float|null
     */
    private ?float $amount;

    /**
     * @var array|null
     */
    private ?array $paymentMethods;

    /**
     * @var array
     */
    private array $customer;

    /**
     * @var array
     */
    private array $billing;

    /**
     * @var array
     */
    private array $shipping;

    /**
     * @var array|null
     */
    private ?array $redirect;

    /**
     * @var array
     */
    private array $webhook;

    /**
     * @return bool
     */
    public function isAutoSettle(): ?bool
    {
        return $this->autoSettle;
    }

    /**
     * @param bool $autoSettle
     */
    public function setAutoSettle(?bool $autoSettle): void
    {
        $this->autoSettle = $autoSettle;
    }

    /**
     * @return string|null
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * @param string|null $currency
     */
    public function setCurrency(?string $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * @return string|null
     */
    public function getRefno(): ?string
    {
        return $this->refno;
    }

    /**
     * @param string|null $refno
     */
    public function setRefno(?string $refno): void
    {
        $this->refno = $refno;
    }

    /**
     * @return float|null
     */
    public function getAmount(): ?string
    {
        return $this->amount;
    }

    /**
     * @param float|null $amount
     */
    public function setAmount(?float $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return array|null
     */
    public function getPaymentMethods(): ?array
    {
        return $this->paymentMethods;
    }

    /**
     * @param array|null $paymentMethods
     */
    public function setPaymentMethods(?array $paymentMethods): void
    {
        $this->paymentMethods = $paymentMethods;
    }

    /**
     * @return array
     */
    public function getCustomer(): array
    {
        return $this->customer;
    }

    /**
     * @param array $customer
     */
    public function setCustomer(array $customer): void
    {
        $this->customer = $customer;
    }

    /**
     * @return array
     */
    public function getBilling(): array
    {
        return $this->billing;
    }

    /**
     * @param array $billing
     */
    public function setBilling(array $billing): void
    {
        $this->billing = $billing;
    }

    /**
     * @return array
     */
    public function getShipping(): array
    {
        return $this->shipping;
    }

    /**
     * @param array $shipping
     */
    public function setShipping(array $shipping): void
    {
        $this->shipping = $shipping;
    }

    /**
     * @return array|null
     */
    public function getRedirect(): ?array
    {
        return $this->redirect;
    }

    /**
     * @param array|null $redirect
     */
    public function setRedirect(?array $redirect): void
    {
        $this->redirect = $redirect;
    }

    /**
     * @return array
     */
    public function getWebhook(): array
    {
        return $this->webhook;
    }

    /**
     * @param array $webhook
     */
    public function setWebhook(array $webhook): void
    {
        $this->webhook = $webhook;
    }


    public function jsonSerialize(): array
    {
        $vars = get_object_vars($this);
        unset($vars['extensions']);

        return array_filter($vars, fn ($vars) => !is_null($vars));
    }

}
