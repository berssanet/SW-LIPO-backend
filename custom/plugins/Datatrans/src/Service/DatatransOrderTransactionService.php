<?php declare(strict_types=1);

namespace Allquanto\Datatrans\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class DatatransOrderTransactionService
{
    private const PAYMENT_CONTEXT_KEY = 'datatrans_payment_context';

    /**
     * @var EntityRepository
     */
    private EntityRepository $orderTransactionRepository;

    public function __construct(
        EntityRepository $orderTransactionRepository
    ) {
        $this->orderTransactionRepository = $orderTransactionRepository;
    }

    public function getOrderTransactionByDatatransTransactionId(string $id, Context $context): ?OrderTransactionEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('customFields.datatrans_payment_context.payment.datatrans_transaction_id', $id)
        );
        return $this->orderTransactionRepository->search($criteria, $context)->getEntities()->first();
    }

    public function getDatatransTransactionId(
        OrderTransactionEntity $orderTransaction
    ): ?string {
        $paymentContext = $this->getPaymentContextFromTransaction($orderTransaction);

        return $paymentContext['payment']['datatrans_transaction_id'] ?? null;
    }

    public function saveDatatransTransactionIdOnOrderTransaction(
        OrderTransactionEntity $orderTransaction,
        Context $context,
        string $datatransTransactionId
    ): void {
        $paymentContext = $this->getPaymentContextFromTransaction($orderTransaction);
        $paymentContext['payment'] = $paymentContext['payment'] ?? [];
        $paymentContext['payment']['datatrans_transaction_id'] = $datatransTransactionId;
        $this->savePaymentContextInTransaction($orderTransaction, $context, $paymentContext);
    }

    private function getPaymentContextFromTransaction(OrderTransactionEntity $orderTransaction): array
    {
        return $orderTransaction->getCustomFields()[self::PAYMENT_CONTEXT_KEY] ?? [];
    }

    private function savePaymentContextInTransaction(
        OrderTransactionEntity $orderTransaction,
        Context $context,
        array $datatransPaymentContext
    ): void {
        $orderTransactionValues = [
            'id' => $orderTransaction->getId(),
            'customFields' => [
                self::PAYMENT_CONTEXT_KEY => $datatransPaymentContext,
            ],
        ];
        $this->orderTransactionRepository->update([$orderTransactionValues], $context);
        $customFields = $orderTransaction->getCustomFields() ?? [];
        $customFields[self::PAYMENT_CONTEXT_KEY] = $datatransPaymentContext;
        $orderTransaction->setCustomFields($customFields);
    }
}
