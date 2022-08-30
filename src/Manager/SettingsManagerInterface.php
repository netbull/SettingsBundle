<?php

namespace NetBull\SettingsBundle\Manager;

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
    public function get(string $name, string $group, $default = null);

    /**
     * Returns all settings as associative name-value array.
     *
     * @param string $group
     *
     * @return array
     */
    public function all(string $group): array;

    /**
     * Sets setting value by its name.
     *
     * @param string $name
     * @param string $group
     * @param mixed $value
     *
     * @return SettingsManagerInterface
     */
    public function set(string $name, $value, string $group): SettingsManagerInterface;

    /**
     * Sets settings' values from associative name-value array.
     *
     * @param array $settings
     * @param string $group
     *
     * @return SettingsManagerInterface
     */
    public function setMany(array $settings, string $group): SettingsManagerInterface;

    /**
     * Clears setting value.
     *
     * @param string $name
     * @param string $group
     *
     * @return SettingsManagerInterface
     */
    public function clear(string $name, string $group): SettingsManagerInterface;
}
