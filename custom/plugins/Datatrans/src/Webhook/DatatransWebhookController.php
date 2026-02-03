<?php declare(strict_types=1);

namespace Allquanto\Datatrans\Webhook;

use Allquanto\Datatrans\Config\DatatransPluginConfigService;
use Allquanto\Datatrans\DatatransApi\Struct\Response\StatusResponseStruct;
use Allquanto\Datatrans\Service\DatatransOrderTransactionService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
#[Package('checkout')]
class DatatransWebhookController
{
    public function __construct(
        private readonly DatatransPluginConfigService $serviceConfig,
        private readonly OrderTransactionStateHandler $transactionStateHandler,
        private readonly DatatransOrderTransactionService $datatransOrderTransactionService,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route(
        path: '/api/_action/datatrans-payment/webhook/execute',
        name: 'api.action.datatrans-payment.webhook.execute',
        methods: ['POST'],
        defaults: ['auth_required' => false]
    )]
    public function executeWebhook(Request $request, Context $context): Response
    {
        $webhookSignature = $request->headers->get('Datatrans-Signature');

        if ($webhookSignature === null) {
            $this->logger->error('Datatrans webhook without signature', ['request' => $request->toArray()]);

            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $webhookPayload = $request->getContent();

        if ($webhookPayload === '') {
            $this->logger->error('Datatrans webhook without payload', ['request' => $request->toArray()]);

            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $parameters = explode(',', $webhookSignature);
        $signatureInfo = array_reduce($parameters, static function (array $carry, string $parameter): array {
            [$key, $value] = explode('=', $parameter, 2);
            $carry[$key] = $value;

            return $carry;
        }, []);

        $config = $this->serviceConfig->getDatatransPluginConfigForSalesChannel();

        $sign = hash_hmac('sha256', $signatureInfo['t'] . $webhookPayload, hex2bin($config->getSign() ?? ''));
        $sign2 = hash_hmac('sha256', $signatureInfo['t'] . $webhookPayload, hex2bin($config->getSign2() ?? ''));

        if (($signatureInfo['s0'] ?? '') !== $sign && ($signatureInfo['s0'] ?? '') !== $sign2) {
            $this->logger->error('Datatrans webhook invalid signature', ['request' => $request->toArray()]);

            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $data = (new StatusResponseStruct())->assign(\json_decode($webhookPayload, true) ?? []);

        $orderTransaction = $this->datatransOrderTransactionService->getOrderTransactionByDatatransTransactionId(
            $data->getTransactionId(),
            $context
        );

        if ($orderTransaction === null) {
            $this->logger->error("Datatrans webhook: order doesn't exist", ['request' => $request->toArray()]);

            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $status = $data->getStatus();

        if (\in_array($status, ['authorized', 'settled', 'transmitted'], true)) {
            $this->transactionStateHandler->paid($orderTransaction->getId(), $context);
        } elseif ($status === 'failed') {
            $this->transactionStateHandler->fail($orderTransaction->getId(), $context);
        } else {
            $this->transactionStateHandler->reopen($orderTransaction->getId(), $context);
        }

        return new Response('', Response::HTTP_OK);
    }
}
