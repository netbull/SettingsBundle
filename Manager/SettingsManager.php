<?php

namespace NetBull\SettingsBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;

use NetBull\SettingsBundle\Entity\Setting;
use NetBull\SettingsBundle\Exception\WrongGroupException;
use NetBull\SettingsBundle\Serializer\SerializerInterface;
use NetBull\SettingsBundle\Exception\UnknownSettingException;

/**
 * Class SettingsManager
 * @package NetBull\SettingsBundle\Manager
 */
class SettingsManager implements SettingsManagerInterface
{
    /**
     * @var array
     */
    private $settings;

    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    private $em;

    /**
     * @var \Doctrine\ORM\EntityRepository
     */
    private $repository;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var array
     */
    private $settingsConfiguration;

    /**
     * @param ObjectManager $em
     * @param SerializerInterface $serializer
     * @param array $settingsConfiguration
     */
    public function __construct(ObjectManager $em, SerializerInterface $serializer, array $settingsConfiguration = []) {
        $this->em = $em;
        $this->repository = $em->getRepository(Setting::class);
        $this->serializer = $serializer;
        $this->settingsConfiguration = $settingsConfiguration;
        $this->settings = array_map(function ($group) { return []; }, $settingsConfiguration);
    }

    /**
     * @param string $name
     * @param string $group
     * @param null $default
     * @return mixed|null
     * @throws UnknownSettingException
     * @throws WrongGroupException
     */
    public function get($name, string $group, $default = null)
    {
        $this->validateSetting($name, $group);
        $this->loadSettings($group);

        return $this->settings[$group][$name] ?? $default;
    }

    /**
     * {@inheritdoc}
     */
    public function all(string $group)
    {
        try {
            $this->loadSettings($group);
        } catch (UnknownSettingException $e) {
            return [];
        }

        return $this->settings[$group] ?? [];
    }

    /**
     * @return array|mixed
     */
    public function allGroups()
    {
        try {
            foreach (array_keys($this->settingsConfiguration) as $group) {
                $this->loadSettings($group);
            }
        } catch (UnknownSettingException $e) {
            return [];
        }

        $output = [];
        foreach ($this->settings as $settings) {
            $output = array_merge($output, $settings);
        }
        return $output ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function set($name, $value, string $group)
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
     *
     * @return array
     *
     * @throws UnknownSettingException
     * @throws WrongGroupException
     */
    public function persistSettingsFromForm(array $settings)
    {
        $groups = [];
        foreach ($settings as $name => $value) {
            // Find the group
            $parts = explode(':', $name);
            $group = $parts[0];
            if (!isset($this->settings[$group])) {
                throw new WrongGroupException($group);
            }

            if (1 === count($parts)) {
                throw new UnknownSettingException($group, '');
            }

            array_shift($parts);
            $name = implode('.', $parts);
            if (!isset($output[$group])) {
                $output[$group] = [];
            }

            $output[$group][$name] = $value;
        }

        return $groups;
    }

    /**
     * {@inheritdoc}
     */
    public function setMany(array $settings, string $group)
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
     * {@inheritdoc}
     */
    public function clear($name, string $group)
    {
        return $this->set($name, null, $group);
    }

    /**
     * Sets setting value to private array. Used for settings' batch saving.
     *
     * @param string $name
     * @param mixed $value
     * @param string $group
     *
     * @return SettingsManager
     *
     * @throws UnknownSettingException
     * @throws WrongGroupException
     */
    private function setWithoutFlush($name, $value, string $group)
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
     *
     * @return SettingsManager
     *
     * @throws \NetBull\SettingsBundle\Exception\UnknownSettingException
     */
    private function flush($names, string $group)
    {
        $names = (array)$names;

        $settings = $this->repository->findBy([
            'name' => $names,
            'grouping' => $group,
        ]);

        // Assert: $settings might be a smaller set than $names

        // For each settings that you are trying to save
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
     *
     * @return Setting|null
     */
    protected function findSettingByName($haystack, $needle)
    {
        foreach ($haystack as $setting) {
            if ($setting->getName() === $needle) {
                return $setting;
            }
        }

        return null;
    }

    /**
     * Checks that $name is valid setting and it's scope is also valid.
     *
     * @param string $name
     * @param string $group
     *
     * @return SettingsManager
     *
     * @throws UnknownSettingException
     * @throws WrongGroupException
     */
    private function validateSetting($name, string $group)
    {
        // Name validation
        if (!is_string($name) || !array_key_exists($name, $this->settingsConfiguration[$group])) {
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
     * @return $this
     * @throws UnknownSettingException
     */
    private function loadSettings(string $group)
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
     *
     * @return array
     *
     * @throws UnknownSettingException
     */
    private function getSettingsFromRepository(string $group)
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
        foreach ($this->repository->findBy([ 'grouping' => $group ]) as $setting) {
            if (array_key_exists($setting->getName(), $settings)) {
                $settings[$setting->getName()] = $this->serializer->unserialize($setting->getValue());
            }
        }

        return $settings;
    }
}
