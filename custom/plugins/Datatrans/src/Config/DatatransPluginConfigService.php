<?php declare(strict_types=1);

namespace Allquanto\Datatrans\Config;

use Shopware\Core\System\SystemConfig\SystemConfigService;

class DatatransPluginConfigService
{
    private const CONFIG_DOMAIN = 'DatatransPayment.config';

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    public function getDatatransPluginConfigForSalesChannel(?string $salesChannelId = null): DatatransPluginConfig
    {
        $rawConfig = $this->systemConfigService->get(self::CONFIG_DOMAIN, $salesChannelId);

        return new DatatransPluginConfig($rawConfig ?? [], true /* inherited */);
    }
}
