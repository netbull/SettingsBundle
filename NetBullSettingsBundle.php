<?php

namespace NetBull\SettingsBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

use NetBull\SettingsBundle\DependencyInjection\NetBullSettingsExtension;

/**
 * Bundle for database-centric settings management.
 *
 * Class NetBullSettingsBundle
 * @package NetBull\SettingsBundle
 */
class NetBullSettingsBundle extends Bundle
{
    /**
     * @return NetBullSettingsExtension|null|\Symfony\Component\DependencyInjection\Extension\ExtensionInterface
     */
    public function getContainerExtension()
    {
        return new NetBullSettingsExtension();
    }
}
