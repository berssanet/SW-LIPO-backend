<?php declare(strict_types=1);

namespace Allquanto\Datatrans\Handlers;

class PostFinancePaymentMethodHandler extends AbstractPaymentMethodHandler
{
    public static function getPaymentMethodCodes(): array
    {
        return [
            "codes" => "getPostFinanceCodes",
            "view" => "getPostFinancePaymentType"
        ];
    }
}
