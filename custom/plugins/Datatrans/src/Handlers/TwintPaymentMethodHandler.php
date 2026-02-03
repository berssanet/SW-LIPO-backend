<?php declare(strict_types=1);

namespace Allquanto\Datatrans\Handlers;

class TwintPaymentMethodHandler extends AbstractPaymentMethodHandler
{
    public static function getPaymentMethodCodes(): array
    {
        return [
            "codes" => "getTwintCodes",
            "view" => "getTwintPaymentType"
        ];
    }
}
