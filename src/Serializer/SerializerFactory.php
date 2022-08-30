<?php

namespace NetBull\SettingsBundle\Serializer;

use Symfony\Component\DependencyInjection\Container;
use NetBull\SettingsBundle\Exception\UnknownSerializerException;

class SerializerFactory
{
    /**
     * @param string $name short name of serializer (ex.: php) or full class name
     *
     * @return SerializerInterface
     * @throws UnknownSerializerException
     */
    public static function create(string $name): SerializerInterface
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
