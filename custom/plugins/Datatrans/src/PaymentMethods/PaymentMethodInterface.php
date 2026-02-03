<?php declare(strict_types=1);

namespace Allquanto\Datatrans\PaymentMethods;

interface PaymentMethodInterface
{
    /**
     * Return name of the payment method.
     *
     * @return array
     */
    public function getTranslations(): array;


    /**
     * Return the payment handler of a plugin.
     *
     * @return string
     */
    public function getPaymentHandler(): string;

}
