<?php

namespace NetBull\SettingsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;

/**
 * Class Configuration
 * @package NetBull\SettingsBundle\DependencyInjection
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('netbull_settings');

        $rootNode
            ->children()
                ->scalarNode('cache_service')->defaultNull()->info('A service implementing Psr\Cache\CacheItemPoolInterface')->end()
                ->integerNode('cache_lifetime')->defaultValue(3600)->end()
                ->enumNode('serialization')
                    ->defaultValue('php')
                    ->values(['php', 'json'])
                ->end()
                ->arrayNode('settings')
                    ->useAttributeAsKey('group')
                    ->addDefaultsIfNotSet()
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('type')->defaultValue('text')->end()

                            ->variableNode('options')
                                ->info('The options given to the form builder')
                                ->defaultValue(array())
                                ->validate()
                                    ->always(function ($v) {
                                        if (!is_array($v)) {
                                            throw new InvalidTypeException();
                                        }

                                        return $v;
                                    })
                                ->end()
                            ->end()
                            ->variableNode('constraints')
                                ->info('The constraints on this option. Example, use constraints found in Symfony\Component\Validator\Constraints')
                                ->defaultValue(array())
                                ->validate()
                                    ->always(function ($v) {
                                        if (!is_array($v)) {
                                            throw new InvalidTypeException();
                                        }

                                        return $v;
                                    })
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return 'netbull_settings';
    }
}
