<?php

namespace NetBull\SettingsBundle\Serializer;

/**
 * Class JsonSerializer
 * @package NetBull\SettingsBundle\Serializer
 */
class JsonSerializer implements SerializerInterface
{
    /**
     * @inheritdoc
     */
    public function serialize($data)
    {
        return json_encode($data);
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        return json_decode($serialized, true);
    }
}
