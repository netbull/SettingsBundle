<?php

namespace NetBull\SettingsBundle\Serializer;

interface SerializerInterface
{
    /**
     * @param mixed $data
     * @return string
     */
    public function serialize($data): string;

    /**
     * @param string $serialized
     * @return mixed
     */
    public function unserialize(string $serialized);
}
