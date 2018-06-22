<?php

namespace NetBull\SettingsBundle\Exception;

/**
 * Class UnknownSettingException
 * @package NetBull\SettingsBundle\Exception
 */
class WrongGroupException extends SettingsException
{
    /**
     * UnknownSettingException constructor.
     * @param $group
     * @param $settingName
     */
    public function __construct($group, $settingName)
    {
        parent::__construct(sprintf('Unknown setting "%s" in group "%s"', $settingName, $group));
    }
}
