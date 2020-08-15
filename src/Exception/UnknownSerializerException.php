<?php

namespace NetBull\SettingsBundle\Exception;

/**
 * Class UnknownSerializerException
 * @package NetBull\SettingsBundle\Exception
 */
class UnknownSerializerException extends SettingsException
{
    /**
     * UnknownSerializerException constructor.
     * @param $serializerClass
     */
    public function __construct($serializerClass)
    {
        parent::__construct(sprintf('Unknown serializer class "%s"', $serializerClass));
    }
}
