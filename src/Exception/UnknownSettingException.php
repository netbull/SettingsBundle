<?php

namespace NetBull\SettingsBundle\Exception;

class UnknownSettingException extends SettingsException
{
    /**
     * @param string $group
     * @param string $settingName
     */
    public function __construct(string $group, string $settingName)
    {
        parent::__construct(sprintf('Unknown setting "%s" in group "%s"', $settingName, $group));
    }
}
