<?php

namespace NetBull\SettingsBundle\Serializer;

/**
 * Class PhpSerializer
 * @package NetBull\SettingsBundle\Serializer
 */
class PhpSerializer implements SerializerInterface
{
    /**
     * @inheritdoc
     */
    public function serialize($data)
    {
        return serialize($data);
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        return unserialize($serialized);
    }
}
