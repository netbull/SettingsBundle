<?php

namespace NetBull\SettingsBundle\Serializer;

class PhpSerializer implements SerializerInterface
{
    /**
     * @param mixed $data
     * @return string
     */
    public function serialize($data): string
    {
        return serialize($data);
    }

    /**
     * @param string $serialized
     * @return mixed
     */
    public function unserialize(string $serialized)
    {
        return unserialize($serialized);
    }
}
