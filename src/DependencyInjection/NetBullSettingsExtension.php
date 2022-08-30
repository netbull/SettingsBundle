<?php

namespace NetBull\SettingsBundle\DependencyInjection;

use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class NetBullSettingsExtension extends Extension
{
    /**
     * @param array $configs
     * @param ContainerBuilder $container
     * @return void
     * @throws Exception
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
        if (null === $config['cache_service']) {
            $container->removeDefinition('netbull.settings.cached_settings_manager');
        } else {
            $container->getDefinition('netbull.settings.cached_settings_manager')
                ->replaceArgument(1, new Reference($config['cache_service']))
                ->replaceArgument(2, $config['cache_lifetime']);

            // set an alias to make sure the cached settings manager is the default
            $container->setAlias('settings_manager', 'netbull.settings.cached_settings_manager');
        }
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return 'netbull_settings';
    }
}
