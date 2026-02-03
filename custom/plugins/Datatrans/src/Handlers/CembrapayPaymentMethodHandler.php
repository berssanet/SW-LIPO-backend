<?php declare(strict_types=1);

namespace Allquanto\Datatrans\Handlers;

class CembrapayPaymentMethodHandler extends AbstractPaymentMethodHandler
{
    public static function getPaymentMethodCodes(): array
    {
        return [
            "codes" => "getCembrapayCodes",
            "view" => "getCembrapayPaymentType"
        ];
    }
}
