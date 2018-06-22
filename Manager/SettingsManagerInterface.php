<?php

namespace NetBull\SettingsBundle\Manager;

use NetBull\SettingsBundle\Entity\Setting;

/**
 * Interface SettingsManagerInterface
 * @package NetBull\SettingsBundle\Manager
 */
interface SettingsManagerInterface
{
    /**
     * Returns setting value by its name.
     *
     * @param string $name
     * @param string $group
     * @param mixed|null $default value to return if the setting is not set
     *
     * @return mixed
     */
    public function get($name, string $group = Setting::GROUP_GENERAL, $default = null);

    /**
     * Returns all settings as associative name-value array.
     *
     * @param string $group
     *
     * @return array
     */
    public function all(string $group = Setting::GROUP_GENERAL);

    /**
     * Sets setting value by its name.
     *
     * @param string $name
     * @param string $group
     * @param mixed $value
     *
     * @return SettingsManagerInterface
     */
    public function set($name, $group = Setting::GROUP_GENERAL, $value);

    /**
     * Sets settings' values from associative name-value array.
     *
     * @param array $settings
     * @param string $group
     *
     * @return SettingsManagerInterface
     */
    public function setMany(array $settings, string $group = Setting::GROUP_GENERAL);

    /**
     * Clears setting value.
     *
     * @param string $name
     * @param string $group
     *
     * @return SettingsManagerInterface
     */
    public function clear($name, string $group = Setting::GROUP_GENERAL);
}
