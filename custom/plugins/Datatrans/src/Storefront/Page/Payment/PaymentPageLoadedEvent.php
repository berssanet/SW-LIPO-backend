<?php declare(strict_types=1);

namespace Allquanto\Datatrans\Storefront\Page\Payment;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\HttpFoundation\Request;

class PaymentPageLoadedEvent extends PageLoadedEvent
{
    protected PaymentPage $page;

    public function __construct(PaymentPage $page, SalesChannelContext $salesChannelContext, Request $request)
    {
        $this->page = $page;
        parent::__construct($salesChannelContext, $request);
    }

    public function getPage(): PaymentPage
    {
        return $this->page;
    }
}
