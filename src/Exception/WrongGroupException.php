<?php

namespace NetBull\SettingsBundle\Exception;

class WrongGroupException extends SettingsException
{
    /**
     * @param string $group
     */
    public function __construct(string $group)
    {
        parent::__construct(sprintf('Unknown group "%s"', $group));
    }
}
