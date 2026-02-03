<?php declare(strict_types=1);

namespace Allquanto\Datatrans\Storefront\Page\Payment;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\GenericPageLoaderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class PaymentPageLoader
{
    private GenericPageLoaderInterface $genericPageLoader;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(GenericPageLoaderInterface $genericPageLoader, EventDispatcherInterface $eventDispatcher)
    {
        $this->genericPageLoader = $genericPageLoader;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function load(Request $request, SalesChannelContext $context): PaymentPage
    {
        $page = $this->genericPageLoader->load($request, $context);
        $page = PaymentPage::createFrom($page);

        // Do additional stuff, e.g. load more data from store api and add it to page
        //$page->setPaymentData(...);

        $this->eventDispatcher->dispatch(
            new PaymentPageLoadedEvent($page, $context, $request)
        );

        return $page;
    }
}
