<?php declare(strict_types=1);

namespace Allquanto\Datatrans\Handlers;

class PayPalPaymentMethodHandler extends AbstractPaymentMethodHandler
{
    public static function getPaymentMethodCodes(): array
    {
        return [
            "codes" => "getPayPalCodes",
            "view" => "getPayPalPaymentType"
        ];
    }
}
