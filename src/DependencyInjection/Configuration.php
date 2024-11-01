<?php

namespace NetBull\SettingsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;

class Configuration implements ConfigurationInterface
{
    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('netbull_settings');
        $rootNode = $treeBuilder->getRootNode();

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
                    ->arrayPrototype()
                        ->arrayPrototype()
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('type')->defaultValue('text')->end()

                                ->variableNode('options')
                                    ->info('The options given to the form builder')
                                    ->defaultValue([])
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
                                    ->defaultValue([])
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
                ->end()
            ->end();

        return $treeBuilder;
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return 'netbull_settings';
    }
}
