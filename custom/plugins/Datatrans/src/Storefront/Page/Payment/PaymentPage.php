<?php declare(strict_types=1);

namespace Allquanto\Datatrans\Storefront\Page\Payment;

use Shopware\Storefront\Page\Page;
use Swag\BasicExample\Core\Content\Example\ExampleEntity;

class PaymentPage extends Page
{
    protected string $datatransTrxId;

    public function getPaymentData(): string
    {
        return $this->datatransTrxId;
    }

    public function setPaymentData(string $datatransTrxId): void
    {
        $this->datatransTrxId = $datatransTrxId;
    }
}
