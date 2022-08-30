<?php

namespace NetBull\SettingsBundle;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use NetBull\SettingsBundle\DependencyInjection\NetBullSettingsExtension;

class NetBullSettingsBundle extends Bundle
{
    /**
     * @return ExtensionInterface|null
     */
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new NetBullSettingsExtension();
    }
}
