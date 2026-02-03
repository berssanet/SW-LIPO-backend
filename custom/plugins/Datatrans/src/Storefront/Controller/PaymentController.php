<?php declare(strict_types=1);

namespace Allquanto\Datatrans\Storefront\Controller;

use Allquanto\Datatrans\Storefront\Page\Payment\PaymentPageLoader;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
#[Package('checkout')]
class PaymentController extends StorefrontController
{
    public function __construct(
        private readonly PaymentPageLoader $paymentPageLoader
    ) {
    }

    #[Route(
        path: '/datatrans/payment',
        name: 'frontend.datatrans.payment',
        methods: ['GET']
    )]
    public function paymentPage(Request $request, SalesChannelContext $context): Response
    {
        $page = $this->paymentPageLoader->load($request, $context);

        return $this->renderStorefront('@AllquantoDatatrans/storefront/page/datatrans/payment/index.html.twig', [
            'datatransTrxId' => $request->get('datatransTrxId'),
            'page' => $page,
        ]);
    }
}
