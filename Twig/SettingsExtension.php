<?php

namespace NetBull\SettingsBundle\Twig;

use NetBull\SettingsBundle\Manager\SettingsManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Extension for retrieving settings in Twig templates.
 */
class SettingsExtension extends AbstractExtension
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
            new TwigFunction('get_setting', [$this, 'getSetting']),
            new TwigFunction('get_all_settings', [$this, 'getAllSettings']),
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
