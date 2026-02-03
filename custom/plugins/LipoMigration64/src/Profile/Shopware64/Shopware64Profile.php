<?php declare(strict_types=1);

namespace LipoMigration64\Profile\Shopware64;

use SwagMigrationAssistant\Profile\Shopware6\Shopware6ProfileInterface;

/**
 * Profile for migrating from Shopware 6.4.20 to 6.7 via API
 * 
 * Uses PROFILE_NAME = 'shopware6major' to leverage the existing
 * Vue credential form components in the Migration Assistant UI.
 * 
 * The version '6.4' differentiates this from the default Shopware6MajorProfile.
 */
class Shopware64Profile implements Shopware6ProfileInterface
{
    /**
     * IMPORTANT: Must match 'shopware6major' to use existing UI components
     * The Vue components are named: swag-migration-profile-{PROFILE_NAME}-api-credential-form
     */
    public const PROFILE_NAME = 'shopware6major';

    public const SOURCE_SYSTEM_NAME = 'Shopware';
    public const SOURCE_SYSTEM_VERSION = '6.4';
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
