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
     * @throws UnknownSettingException
     */
    private function flush($names, string $group)
    {
        $names = (array)$names;

        $settings = $this->repository->findBy([
            'name' => $names,
            'group' => $group,
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
                $setting->setGroup($group);
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
        if (!is_string($name) || !array_key_exists($name, $this->settingsConfiguration)) {
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
        foreach ($this->repository->findBy([ 'group' => $group ]) as $setting) {
            if (array_key_exists($setting->getName(), $settings)) {
                $settings[$setting->getName()] = $this->serializer->unserialize($setting->getValue());
            }
        }

        return $settings;
    }
}
