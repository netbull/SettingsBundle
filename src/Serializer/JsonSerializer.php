<?php

namespace NetBull\SettingsBundle\Serializer;

class JsonSerializer implements SerializerInterface
{
    /**
     * @param $data
     * @return false|string
     */
    public function serialize($data): string
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
    public function unserialize(string $serialized)
    {
        return json_decode($serialized, true);
    }
}
