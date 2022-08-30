<?php

namespace NetBull\SettingsBundle\Exception;

class UnknownSerializerException extends SettingsException
{
    /**
     * @param string $serializerClass
     */
    public function __construct(string $serializerClass)
    {
        parent::__construct(sprintf('Unknown serializer class "%s"', $serializerClass));
    }
}
