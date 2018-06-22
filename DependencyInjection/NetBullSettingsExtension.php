<?php

namespace NetBull\SettingsBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class NetBullSettingsExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        foreach ($config as $key => $value) {
            $container->setParameter('settings_manager.'.$key, $value);
        }

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        // Configure the correct storage
        if ($config['cache_service'] === null) {
            $container->removeDefinition('netbull.settings.cached_settings_manager');
        } else {
            $container->getDefinition('netbull.settings.cached_settings_manager')
                ->replaceArgument(1, new Reference($config['cache_service']))
                ->replaceArgument(2, $config['cache_lifetime']);

            // set an alias to make sure the cached settings manager is the default
            $container->setAlias('settings_manager', 'netbull.settings.cached_settings_manager');
        }
    }
}
