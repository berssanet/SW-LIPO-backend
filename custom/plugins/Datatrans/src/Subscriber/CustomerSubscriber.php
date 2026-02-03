<?php declare(strict_types = 1);

namespace Allquanto\Datatrans\Subscriber;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\CustomerEvents;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Profiling\Profiler;
use Shopware\Core\System\Language\LanguageEntity;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CustomerSubscriber implements EventSubscriberInterface
{
    private EntityRepository $languageRepository;

    /**
     * @param EntityRepository $languageRepository
     */
    public function __construct(
        EntityRepository $languageRepository
    )
    {
        $this->languageRepository = $languageRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CustomerEvents::CUSTOMER_LOADED_EVENT => 'onCustomerLoad'
        ];
    }

    public function onCustomerLoad(EntityLoadedEvent $event)
    {

        if(count($event->getEntities()) != 1){
            return;
        }

        Profiler::trace('customer::datatrans::load::collect', function () use ($event): void {
            /** @var CustomerEntity $customer */
            foreach ($event->getEntities() as $customer)
            {
                $customer->setLanguage($this->getLanguage($customer->getLanguageId(), $event->getContext()));
            }
        });

    }

    private function getLanguage(string $id, Context $context): LanguageEntity
    {
        $criteria = new Criteria([$id]);
        $criteria->addAssociation('locale');
        return $this->languageRepository->search($criteria, $context)->getEntities()->first();
    }

}
