<?php

namespace NetBull\SettingsBundle\Manager;

use Doctrine\ORM\EntityManagerInterface;
use NetBull\SettingsBundle\Entity\Setting;
use NetBull\SettingsBundle\Exception\WrongGroupException;
use NetBull\SettingsBundle\Serializer\SerializerInterface;
use NetBull\SettingsBundle\Exception\UnknownSettingException;

class SettingsManager implements SettingsManagerInterface
{
    /**
     * @var array
     */
    private $settings;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var array
     */
    private $settingsConfiguration;

    /**
     * @param EntityManagerInterface $em
     * @param SerializerInterface $serializer
     * @param array $settingsConfiguration
     */
    public function __construct(EntityManagerInterface $em, SerializerInterface $serializer, array $settingsConfiguration = [])
    {
        $this->em = $em;
        $this->serializer = $serializer;
        $this->settingsConfiguration = $settingsConfiguration;
        $this->settings = array_map(function () { return []; }, $settingsConfiguration);
    }

    /**
     * @param string $name
     * @param string $group
     * @param null $default
     * @return mixed|null
     * @throws UnknownSettingException
     * @throws WrongGroupException
     */
    public function get(string $name, string $group, $default = null)
    {
        $this->validateSetting($name, $group);
        $this->loadSettings($group);

        return $this->settings[$group][$name] ?? $default;
    }

    /**
     * @param string $group
     * @param bool $forForm
     * @return array
     * @throws WrongGroupException
     */
    public function all(string $group, bool $forForm = false): array
    {
        if (!isset($this->settings[$group])) {
            throw new WrongGroupException($group);
        }

        try {
            $this->loadSettings($group);
        } catch (UnknownSettingException $e) {
            return [];
        }

        $output = [];
        if ($forForm) {
            foreach ($this->settings[$group] as $name => $setting) {
                $output[$group . '_' . $name] = $setting;
            }
        } else {
            $output = $this->settings[$group];
        }
        return $output;
    }

    /**
     * @param bool $forForm
     * @return array
     */
    public function allGroups(bool $forForm = false): array
    {
        try {
            foreach (array_keys($this->settingsConfiguration) as $group) {
                $this->loadSettings($group);
            }
        } catch (UnknownSettingException $e) {
            return [];
        }

        $output = [];
        foreach ($this->settings as $group => $settings) {
            if ($forForm) {
                foreach ($settings as $name => $setting) {
                    $output[$group . '_' . $name] = $setting;
                }
            } else {
                $output = array_merge($output, $settings);
            }
        }
        return $output ?? [];
    }

    /**
     * @param string $name
     * @param $value
     * @param string $group
     * @return SettingsManagerInterface
     */
    public function set(string $name, $value, string $group): SettingsManagerInterface
    {
        try {
            $this->setWithoutFlush($name, $value, $group);
        } catch (UnknownSettingException | WrongGroupException $e) {
            return $this;
        }

        try {
            return $this->flush($name, $group);
        } catch (UnknownSettingException $e) {
            return $this;
        }
    }

    /**
     * @param array $settings
     * @return array
     * @throws UnknownSettingException
     * @throws WrongGroupException
     */
    public function persistSettingsFromForm(array $settings): array
    {
        $output = [];
        foreach ($settings as $name => $value) {
            // Find the group
            $parts = explode('_', $name);
            $group = $parts[0];
            if (!isset($this->settings[$group])) {
                throw new WrongGroupException($group);
            }

            if (1 === count($parts)) {
                throw new UnknownSettingException($group, '');
            }

            array_shift($parts);
            $name = implode('_', $parts);
            if (!isset($output[$group])) {
                $output[$group] = [];
            }

            $output[$group][$name] = $value;
        }

        return $output;
    }

    /**
     * @param array $settings
     * @param string $group
     * @return SettingsManagerInterface
     */
    public function setMany(array $settings, string $group): SettingsManagerInterface
    {
        foreach ($settings as $name => $value) {
            try {
                $this->setWithoutFlush($name, $value, $group);
            } catch (UnknownSettingException | WrongGroupException $e) {
                return $this;
            }
        }

        try {
            return $this->flush(array_keys($settings), $group);
        } catch (UnknownSettingException $e) {
            return $this;
        }
    }

    /**
     * @param string $name
     * @param string $group
     * @return SettingsManagerInterface
     */
    public function clear(string $name, string $group): SettingsManagerInterface
    {
        return $this->set($name, null, $group);
    }

    /**
     * Sets setting value to private array. Used for settings' batch saving.
     *
     * @param string $name
     * @param mixed $value
     * @param string $group
     * @return SettingsManagerInterface
     * @throws UnknownSettingException
     * @throws WrongGroupException
     */
    private function setWithoutFlush(string $name, $value, string $group): SettingsManagerInterface
    {
        $this->validateSetting($name, $group);
        $this->loadSettings($group);

        $this->settings[$group][$name] = $value;

        return $this;
    }

    /**
     * Flushes settings defined by $names to database.
     *
     * @param string|array $names
     * @param string $group
     * @return SettingsManagerInterface
     * @throws UnknownSettingException
     */
    private function flush($names, string $group): SettingsManagerInterface
    {
        $names = (array)$names;

        $settings = $this->em->getRepository(Setting::class)->findBy([
            'name' => $names,
            'grouping' => $group,
        ]);

        // Assert: $settings might be a smaller set than $names

        // For each setting that you are trying to save
        foreach ($names as $name) {
            try {
                $value = $this->get($name, $group);
            } catch (WrongGroupException $e) {
                continue;
            }

            /** @var Setting $setting */
            $setting = $this->findSettingByName($settings, $name);

            if (!$setting) {
                // if the setting does not exist in DB, create it
                $setting = new Setting();
                $setting->setName($name);
                $setting->setGrouping($group);
                $this->em->persist($setting);
            }

            $setting->setValue($this->serializer->serialize($value));
        }

        $this->em->flush();

        return $this;
    }

    /**
     * Find a setting by name form an array of settings.
     *
     * @param Setting[] $haystack
     * @param string $needle
     * @return Setting|null
     */
    protected function findSettingByName(array $haystack, string $needle): ?Setting
    {
        foreach ($haystack as $setting) {
            if ($setting->getName() === $needle) {
                return $setting;
            }
        }

        return null;
    }

    /**
     * Checks that $name is valid setting, and it's scope is also valid.
     *
     * @param string $name
     * @param string $group
     * @return SettingsManagerInterface
     * @throws UnknownSettingException
     * @throws WrongGroupException
     */
    private function validateSetting(string $name, string $group): SettingsManagerInterface
    {
        // Name validation
        if (!array_key_exists($group, $this->settingsConfiguration)) {
            throw new WrongGroupException($group);
        }

        // Name validation
        if (!array_key_exists($name, $this->settingsConfiguration[$group])) {
            throw new UnknownSettingException($group, $name);
        }

        // Group validation
        if (!isset($this->settings[$group])) {
            throw new WrongGroupException($group);
        }

        return $this;
    }

    /**
     * @param string $group
     * @return SettingsManagerInterface
     * @throws UnknownSettingException
     */
    private function loadSettings(string $group): SettingsManagerInterface
    {
        // Global settings
        if (empty($this->settings[$group])) {
            $this->settings[$group] = $this->getSettingsFromRepository($group);
        }

        return $this;
    }

    /**
     * Retrieves settings from repository.
     *
     * @param string $group
     * @return array
     * @throws UnknownSettingException
     */
    private function getSettingsFromRepository(string $group): array
    {
        $settings = [];

        foreach (array_keys($this->settingsConfiguration[$group]) as $name) {
            try {
                $this->validateSetting($name, $group);
                $settings[$name] = null;
            } catch (WrongGroupException $e) {
                continue;
            }
        }

        /** @var Setting $setting */
        foreach ($this->em->getRepository(Setting::class)->findBy([ 'grouping' => $group ]) as $setting) {
            if (array_key_exists($setting->getName(), $settings)) {
                $settings[$setting->getName()] = $this->serializer->unserialize($setting->getValue());
            }
        }

        return $settings;
    }
}
