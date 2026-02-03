<?php declare(strict_types=1);

namespace LipoCore\Subscriber;

use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiRequestSubscriber implements EventSubscriberInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onApiRequest',
        ];
    }

    public function onApiRequest(RequestEvent $event): void
    {
        $route = $event->getRequest()->attributes->get('_route');
        $controller = $event->getRequest()->attributes->get('_controller');

        if (!$route || $route != 'api.action.sync') {
            return;
        }

        if (!$controller || $controller != 'Shopware\Core\Framework\Api\Controller\SyncController::sync') {
            return;
        }


        $payload = \json_decode($event->getRequest()->getContent(), true);

        if ($payload) {
            $this->logger->info(
                'API Payload Received',
                ['payload' => $payload]
            );
        }
    }
}
