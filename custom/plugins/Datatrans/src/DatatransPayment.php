<?php declare(strict_types=1);

namespace Allquanto\Datatrans;

use Allquanto\Datatrans\Installation\DatatransPaymentInstaller;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;

#[Package('checkout')]
class DatatransPayment extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        $this->getInstaller($installContext->getContext())->install();
        parent::install($installContext);
    }

    public function update(UpdateContext $updateContext): void
    {
        $this->getInstaller($updateContext->getContext())->update();
        parent::update($updateContext);
    }

    public function activate(ActivateContext $activateContext): void
    {
        $this->getInstaller($activateContext->getContext())->activate();
        parent::activate($activateContext);
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        $this->getInstaller($deactivateContext->getContext())->deactivate();
        parent::deactivate($deactivateContext);
    }

    private function getInstaller(\Shopware\Core\Framework\Context $context): DatatransPaymentInstaller
    {
        return new DatatransPaymentInstaller(
            $context,
            $this->container->get(PluginIdProvider::class),
            $this->container->get('payment_method.repository'),
            $this->container->get('language.repository')
        );
    }
}
