<?php

namespace NetBull\SettingsBundle\Serializer;

/**
 * Interface SerializerInterface
 * @package NetBull\SettingsBundle\Serializer
 */
interface SerializerInterface
{
    /**
     * @param mixed $data
     *
     * @return string
     */
    public function serialize($data);

    /**
     * @param string $serialized
     *
     * @return mixed
     */
    public function unserialize($serialized);
}
