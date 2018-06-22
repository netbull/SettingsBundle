<?php

namespace NetBull\SettingsBundle\Exception;

/**
 * Class WrongGroupException
 * @package NetBull\SettingsBundle\Exception
 */
class WrongGroupException extends SettingsException
{
    /**
     * WrongGroupException constructor.
     * @param $group
     */
    public function __construct($group)
    {
        parent::__construct(sprintf('Unknown group "%s"', $group));
    }
}
