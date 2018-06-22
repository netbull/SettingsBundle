<?php

namespace NetBull\SettingsBundle\Exception;

/**
 * Class UnknownSettingException
 * @package NetBull\SettingsBundle\Exception
 */
class UnknownSettingException extends SettingsException
{
    /**
     * UnknownSettingException constructor.
     * @param $settingName
     */
    public function __construct($settingName)
    {
        parent::__construct(sprintf('Unknown setting "%s"', $settingName));
    }
}
