<?php

namespace NetBull\SettingsBundle\Serializer;

class JsonSerializer implements SerializerInterface
{
    /**
     * @param mixed $data
     * @return string
     */
    public function serialize(mixed $data): string
    {
        if ($result = json_encode($data)) {
            return $result;
        }

        return '';
    }

    /**
     * @param string $serialized
     * @return mixed
     */
    public function unserialize(string $serialized): mixed
    {
        return json_decode($serialized, true);
    }
}
