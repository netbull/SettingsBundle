<?php

namespace NetBull\SettingsBundle\Twig;

use NetBull\SettingsBundle\Manager\SettingsManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SettingsExtension extends AbstractExtension
{
    /**
     * @var SettingsManagerInterface
     */
    private SettingsManagerInterface $settingsManager;

    /**
     * @param SettingsManagerInterface $settingsManager
     */
    public function __construct(SettingsManagerInterface $settingsManager)
    {
        $this->settingsManager = $settingsManager;
    }

    public function getFunctions(): array
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
     * @param string|null $default
     * @return mixed
     */
    public function getSetting(string $name, string $group, string $default = null): mixed
    {
        return $this->settingsManager->get($name, $group, $default);
    }

    /**
     * Proxy to SettingsManager::all.
     *
     * @param string $group
     * @return array
     */
    public function getAllSettings(string $group): array
    {
        return $this->settingsManager->all($group);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'settings_extension';
    }
}
