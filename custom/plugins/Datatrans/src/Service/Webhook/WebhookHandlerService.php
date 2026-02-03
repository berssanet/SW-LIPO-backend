<?php declare(strict_types=1);

namespace Allquanto\Datatrans\Service\Webhook;

use Allquanto\Datatrans\DatatransApi\Struct\Response\StatusResponseStruct;
use Allquanto\Datatrans\Service\DatatransOrderTransactionService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Framework\Context;

class WebhookHandlerService
{
    private OrderTransactionStateHandler $transactionStateHandler;
    private DatatransOrderTransactionService $datatransOrderTransactionService;
    private LoggerInterface $logger;

    public function __construct(
        OrderTransactionStateHandler $transactionStateHandler,
        DatatransOrderTransactionService $datatransOrderTransactionService,
        LoggerInterface $logger
    ) {
        $this->transactionStateHandler = $transactionStateHandler;
        $this->datatransOrderTransactionService = $datatransOrderTransactionService;
        $this->logger = $logger;
    }

    public function process(string $webhookPayload, Context $context): bool
    {
        $data = (new StatusResponseStruct())->assign(\json_decode($webhookPayload, true) ?? []);

        $orderTransaction = $this->datatransOrderTransactionService->getOrderTransactionByDatatransTransactionId(
            $data->getTransactionId(),
            $context
        );

        if (!$orderTransaction) {
            $this->logger->error("Datatrans webhook: Order transaction not found for Datatrans transaction ID: " . $data->getTransactionId(), \json_decode($webhookPayload, true));
            return false;
        }

        $transactionId = $orderTransaction->getId();
        $currentStatus = $orderTransaction->getStateMachineState()->getTechnicalName();

        switch ($data->getStatus()) {
            case 'authorized':
            case 'settled':
            case 'transmitted':
                if ($currentStatus !== 'paid') {
                    $this->transactionStateHandler->paid($transactionId, $context);
                }
                break;
            case 'failed':
                if ($currentStatus !== 'failed') {
                    $this->transactionStateHandler->fail($transactionId, $context);
                }
                break;
            case 'canceled':
                 if ($currentStatus !== 'cancelled') {
                    $this->transactionStateHandler->cancel($transactionId, $context);
                }
                break;
            default:
                if ($currentStatus === 'in_progress') {
                     $this->transactionStateHandler->reopen($transactionId, $context);
                }
                break;
        }

        return true;
    }
}
