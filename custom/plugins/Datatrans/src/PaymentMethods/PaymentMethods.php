<?php declare(strict_types=1);

namespace Allquanto\Datatrans\PaymentMethods;

class PaymentMethods
{
    const PAYMENT_METHODS = [
        LipoCardPaymentMethod::class,
        CardsPaymentMethod::class,
        PostFinancePaymentMethod::class,
        TwintPaymentMethod::class,
        PayPalPaymentMethod::class,
        PowerpayPaymentMethod::class,
        CembrapayPaymentMethod::class
    ];
}

