<?php

namespace NetBull\SettingsBundle\Serializer;

class PhpSerializer implements SerializerInterface
{
    /**
     * @param mixed $data
     * @return string
     */
    public function serialize(mixed $data): string
    {
        return serialize($data);
    }

    /**
     * @param string $serialized
     * @return mixed
     */
    public function unserialize(string $serialized): mixed
    {
        return unserialize($serialized);
    }
}
