<?php

namespace NetBull\SettingsBundle\Twig;

use NetBull\SettingsBundle\Manager\SettingsManagerInterface;

/**
 * Extension for retrieving settings in Twig templates.
 */
class SettingsExtension extends \Twig_Extension
{
    /**
     * @var SettingsManagerInterface
     */
    private $settingsManager;

    /**
     * SettingsExtension constructor.
     * @param SettingsManagerInterface $settingsManager
     */
    public function __construct(SettingsManagerInterface $settingsManager)
    {
        $this->settingsManager = $settingsManager;
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('get_setting', [$this, 'getSetting']),
            new \Twig_SimpleFunction('get_all_settings', [$this, 'getAllSettings']),
        );
    }

    /**
     * Proxy to SettingsManager::get.
     *
     * @param string $name
     * @param string $group
     * @param string $default
     *
     * @return mixed
     */
    public function getSetting($name, string $group, $default = null)
    {
        return $this->settingsManager->get($name, $group, $default);
    }

    /**
     * Proxy to SettingsManager::all.
     *
     * @param string $group
     *
     * @return array
     */
    public function getAllSettings(string $group)
    {
        return $this->settingsManager->all($group);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'settings_extension';
    }
}
