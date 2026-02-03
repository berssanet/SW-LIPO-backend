<?php declare(strict_types=1);

namespace Allquanto\Datatrans\PaymentMethods;

use Allquanto\Datatrans\Handlers\CembrapayPaymentMethodHandler;

class CembrapayPaymentMethod implements PaymentMethodInterface
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
                'name' => 'CembraPay',
            ],
            'en-GB' => [
                'description' => '',
                'name' => 'CembraPay',
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
        return CembrapayPaymentMethodHandler::class;
    }

}
