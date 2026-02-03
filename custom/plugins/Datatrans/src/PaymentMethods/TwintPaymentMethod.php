<?php declare(strict_types=1);

namespace Allquanto\Datatrans\PaymentMethods;

use Allquanto\Datatrans\Handlers\TwintPaymentMethodHandler;

class TwintPaymentMethod implements PaymentMethodInterface
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
                'name' => 'TWINT',
            ],
            'en-GB' => [
                'description' => '',
                'name' => 'TWINT',
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
        return TwintPaymentMethodHandler::class;
    }

}
