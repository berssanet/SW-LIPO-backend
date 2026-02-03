<?php declare(strict_types=1);

namespace Allquanto\Datatrans\PaymentMethods;

use Allquanto\Datatrans\Handlers\LipoCardPaymentMethodHandler;

class LipoCardPaymentMethod implements PaymentMethodInterface
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
                'name' => 'LIPO Mastercard',
            ],
            'en-GB' => [
                'description' => '',
                'name' => 'LIPO Mastercard',
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
        return LipoCardPaymentMethodHandler::class;
    }

}
