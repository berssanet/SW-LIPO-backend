<?php declare(strict_types=1);

namespace LipoMigration64\Profile\Shopware64;

use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

/**
 * Profile for migrating from Shopware 6.4.20 to 6.7
 * 
 * Implements ShopwareProfileInterface to use existing ShopwareLocalGateway
 * which provides the database credentials form in the Migration Assistant UI.
 */
class Shopware64Profile implements ShopwareProfileInterface
{
    public const PROFILE_NAME = 'shopware64';
    public const SOURCE_SYSTEM_NAME = 'Shopware';
    public const SOURCE_SYSTEM_VERSION = '6.4.20';
    public const AUTHOR_NAME = 'Lipo AG';
    public const ICON_PATH = '/swagmigrationassistant/static/img/migration-assistant-plugin.svg';

    public function getName(): string
    {
        return self::PROFILE_NAME;
    }

    public function getSourceSystemName(): string
    {
        return self::SOURCE_SYSTEM_NAME;
    }

    public function getVersion(): string
    {
        return self::SOURCE_SYSTEM_VERSION;
    }

    public function getAuthorName(): string
    {
        return self::AUTHOR_NAME;
    }

    public function getIconPath(): string
    {
        return self::ICON_PATH;
    }
}
