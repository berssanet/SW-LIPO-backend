<?php declare(strict_types=1);

namespace Allquanto\Datatrans\Handlers;

class CardsPaymentMethodHandler extends AbstractPaymentMethodHandler
{
    public static function getPaymentMethodCodes(): array
    {
        return [
            "codes" => "getCardBrands",
            "view" => "getCardPaymentType"
        ];
    }
}
