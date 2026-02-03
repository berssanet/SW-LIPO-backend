<?php declare(strict_types=1);

namespace Allquanto\Datatrans\Handlers;

use Allquanto\Datatrans\DatatransApi\DatatransClientFactory;
use Allquanto\Datatrans\Service\DatatransOrderTransactionService;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AbstractPaymentHandler;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

#[Package('checkout')]
abstract class AbstractPaymentMethodHandler extends AbstractPaymentHandler
{
    public function __construct(
        private readonly OrderTransactionStateHandler $transactionStateHandler,
        private readonly DatatransOrderTransactionService $datatransOrderTransactionService,
        private readonly DatatransClientFactory $datatransClientFactory,
        private readonly EntityRepository $orderTransactionRepository
    ) {
    }

    abstract public static function getPaymentMethodCodes(): array;

    public function supports(
        PaymentHandlerType $type,
        string $paymentMethodId,
        Context $context
    ): bool {
        return $type === PaymentHandlerType::ASYNC;
    }

    public function pay(
        Request $request,
        PaymentTransactionStruct $transaction,
        Context $context,
        ?Struct $validateStruct
    ): ?RedirectResponse {
        $orderTransactionId = $transaction->getOrderTransactionId();
        $orderTransaction = $this->getOrderTransaction($orderTransactionId, $context);

        if ($orderTransaction === null) {
            throw PaymentException::asyncProcessInterrupted(
                $orderTransactionId,
                'Order transaction not found'
            );
        }

        $order = $orderTransaction->getOrder();
        if ($order === null) {
            throw PaymentException::asyncProcessInterrupted(
                $orderTransactionId,
                'Order not found'
            );
        }

        $datatransTransactionId = $this->datatransOrderTransactionService->getDatatransTransactionId($orderTransaction);

        if ($datatransTransactionId !== null) {
            $paymentState = $this->getPaymentState($order->getSalesChannelId(), $datatransTransactionId, $orderTransactionId);

            if (\in_array($paymentState, ['authorized', 'settled', 'transmitted'], true)) {
                throw PaymentException::asyncProcessInterrupted(
                    $orderTransactionId,
                    'An order is already settled'
                );
            }
        }

        try {
            $datatransTransaction = $this->datatransClientFactory->createFromOrder(
                $order,
                static::getPaymentMethodCodes(),
                $transaction->getReturnUrl() ?? ''
            );
        } catch (\Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $orderTransactionId,
                'An error occurred during the communication with external payment gateway: ' . $e->getMessage()
            );
        }

        $this->datatransOrderTransactionService->saveDatatransTransactionIdOnOrderTransaction(
            $orderTransaction,
            $context,
            $datatransTransaction->getTransactionId()
        );

        return new RedirectResponse($datatransTransaction->getRedirectUrl());
    }

    public function finalize(
        Request $request,
        PaymentTransactionStruct $transaction,
        Context $context
    ): void {
        $orderTransactionId = $transaction->getOrderTransactionId();
        $orderTransaction = $this->getOrderTransaction($orderTransactionId, $context);

        if ($orderTransaction === null) {
            throw PaymentException::invalidTransaction($orderTransactionId);
        }

        $order = $orderTransaction->getOrder();
        if ($order === null) {
            throw PaymentException::invalidTransaction($orderTransactionId);
        }

        $datatransTransactionId = $this->datatransOrderTransactionService->getDatatransTransactionId($orderTransaction);

        if ($datatransTransactionId === null) {
            throw PaymentException::invalidTransaction($orderTransactionId);
        }

        $paymentState = $this->getPaymentState($order->getSalesChannelId(), $datatransTransactionId, $orderTransactionId);

        if ($paymentState === 'canceled') {
            throw PaymentException::customerCanceled(
                $orderTransactionId,
                'Customer canceled the payment on the Datatrans page'
            );
        }

        if ($paymentState === 'failed') {
            throw PaymentException::syncProcessInterrupted(
                $orderTransactionId,
                'Customer payment failed on the Datatrans page'
            );
        }

        if (\in_array($paymentState, ['authorized', 'settled', 'transmitted'], true)) {
            $this->transactionStateHandler->paid($orderTransactionId, $context);
        } else {
            $this->transactionStateHandler->reopen($orderTransactionId, $context);
        }
    }

    private function getOrderTransaction(string $orderTransactionId, Context $context): ?OrderTransactionEntity
    {
        $criteria = new Criteria([$orderTransactionId]);
        $criteria->addAssociation('order');
        $criteria->addAssociation('order.currency');
        $criteria->addAssociation('order.billingAddress.country');
        $criteria->addAssociation('order.billingAddress.salutation');
        $criteria->addAssociation('order.orderCustomer.customer');
        $criteria->addAssociation('order.deliveries.shippingOrderAddress.country');

        return $this->orderTransactionRepository->search($criteria, $context)->first();
    }

    private function getPaymentState(string $salesChannelId, string $datatransTransactionId, string $orderTransactionId): string
    {
        try {
            return $this->datatransClientFactory->status(
                $salesChannelId,
                $datatransTransactionId
            )->getStatus();
        } catch (\Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $orderTransactionId,
                'An error occurred during the communication with external payment gateway: ' . $e->getMessage()
            );
        }
    }
}
