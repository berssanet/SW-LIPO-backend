<?php declare(strict_types=1);

namespace Allquanto\Datatrans\Service\Webhook;

use Allquanto\Datatrans\Config\DatatransPluginConfig;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WebhookSignatureValidator
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function validateSignature(Request $request, DatatransPluginConfig $config): bool
    {
        $webhookSignature = $request->headers->get('Datatrans-Signature');

        if (!$webhookSignature) {
            $this->logger->error('Datatrans webhook without signature', $request->toArray());
            return false;
        }

        $webhookPayload = $request->getContent();
        if (!$webhookPayload) {
            $this->logger->error('Datatrans webhook without payload', $request->toArray());
            return false;
        }

        $parameters = explode(',', $webhookSignature);
        $signatureInfo = array_reduce($parameters, static function ($carry, $parameter) {
            list($key, $value) = explode('=', $parameter, 2);
            $carry[$key] = $value;
            return $carry;
        }, []);

        if (!isset($signatureInfo['t'], $signatureInfo['s0'])) {
            $this->logger->error('Datatrans webhook with invalid signature format', $request->toArray());
            return false;
        }
        
        $primarySign = $config->getSing();
        $secondarySign = $config->getSing2();

        if (empty($primarySign)) {
            $this->logger->error('Datatrans webhook signing key is not configured.');
            return false;
        }

        $expectedSign = hash_hmac('sha256', $signatureInfo['t'] . $webhookPayload, hex2bin($primarySign));
        if ($signatureInfo['s0'] === $expectedSign) {
            return true;
        }

        if (!empty($secondarySign)) {
            $expectedSign2 = hash_hmac('sha256', $signatureInfo['t'] . $webhookPayload, hex2bin($secondarySign));
            if ($signatureInfo['s0'] === $expectedSign2) {
                return true;
            }
        }
        
        $this->logger->error('Datatrans webhook with invalid signature', $request->toArray());
        return false;
    }
}
