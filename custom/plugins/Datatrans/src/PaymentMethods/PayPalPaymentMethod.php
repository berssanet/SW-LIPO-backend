<?php declare(strict_types=1);

namespace Allquanto\Datatrans\PaymentMethods;

use Allquanto\Datatrans\Handlers\PayPalPaymentMethodHandler;

class PayPalPaymentMethod implements PaymentMethodInterface
{
    /**
     * {@inheritDoc}
     *
     * @return array
     */
    public function getTranslations(): array
    {
        return [
            'de-DE' => [
                'description' => '',
                'name' => 'PayPal',
            ],
            'en-GB' => [
                'description' => '',
                'name' => 'PayPal',
            ],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    public function getPaymentHandler(): string
    {
        return PayPalPaymentMethodHandler::class;
    }

}
