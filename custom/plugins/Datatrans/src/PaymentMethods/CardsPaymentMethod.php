<?php declare(strict_types=1);

namespace Allquanto\Datatrans\PaymentMethods;

use Allquanto\Datatrans\Handlers\CardsPaymentMethodHandler;

class CardsPaymentMethod implements PaymentMethodInterface
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
                'name' => 'Kredit- oder Debitkarte',
            ],
            'en-GB' => [
                'description' => '',
                'name' => 'Credit or debit card',
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
        return CardsPaymentMethodHandler::class;
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     */
    public function getPaymentCodes(): array
    {
        return CardsPaymentMethodHandler::class;
    }
}
