<?php declare(strict_types=1);

namespace Allquanto\Datatrans\Handlers;

class LipoCardPaymentMethodHandler extends AbstractPaymentMethodHandler
{
    public static function getPaymentMethodCodes(): array
    {
        return [
            "codes" => "getLipoCardBrands",
            "view" => "getLipoCardPaymentType"
        ];
    }
}
