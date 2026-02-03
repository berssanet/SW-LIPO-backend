<?php declare(strict_types=1);

namespace Allquanto\Datatrans\Handlers;

class PowerpayPaymentMethodHandler extends AbstractPaymentMethodHandler
{
    public static function getPaymentMethodCodes(): array
    {
        return [
            "codes" => "getPowerpayCodes",
            "view" => "getPowerpayPaymentType"
        ];
    }
}
