<?php

namespace NetBull\SettingsBundle\Serializer;

use Symfony\Component\DependencyInjection\Container;

use NetBull\SettingsBundle\Exception\UnknownSerializerException;

/**
 * Class SerializerFactory
 * @package NetBull\SettingsBundle\Serializer
 */
class SerializerFactory
{
    /**
     * @param string $name short name of serializer (ex.: php) or full class name
     *
     * @throws \NetBull\SettingsBundle\Exception\UnknownSerializerException
     *
     * @return SerializerInterface
     */
    public static function create($name)
    {
        $serializerClass = 'NetBull\\SettingsBundle\\Serializer\\' . Container::camelize($name) . 'Serializer';

        if (class_exists($serializerClass)) {
            return new $serializerClass();
        } else {
            $serializerClass = $name;

            if (class_exists($serializerClass)) {
                $serializer = new $serializerClass();
                if ($serializer instanceof SerializerInterface) {
                    return $serializer;
                }
            }
        }

        throw new UnknownSerializerException($serializerClass);
    }
}
