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
     */
    public function get($name, string $group = Setting::GROUP_GENERAL, $default = null)
    {
        $this->validateSetting($name);
        $this->loadSettings();

        return $this->settings[$name] ?? $default;
    }

    /**
     * {@inheritdoc}
     */
    public function all(string $group = Setting::GROUP_GENERAL)
    {
        $this->loadSettings($group);

        if ($owner === null) {
            return $this->globalSettings;
        }

        $settings = $this->ownerSettings[$owner->getSettingIdentifier()];

        // If some user setting is not defined, please use the value from global
        foreach ($settings as $key => $value) {
            if ($value === null && isset($this->globalSettings[$key])) {
                $settings[$key] = $this->globalSettings[$key];
            }
        }

        return $settings;
    }

    /**
     * {@inheritdoc}
     */
    public function set($name, $value, SettingsOwnerInterface $owner = null)
    {
        $this->setWithoutFlush($name, $value, $owner);

        return $this->flush($name, $owner);
    }

    /**
     * {@inheritdoc}
     */
    public function setMany(array $settings, SettingsOwnerInterface $owner = null)
    {
        foreach ($settings as $name => $value) {
            $this->setWithoutFlush($name, $value, $owner);
        }

        return $this->flush(array_keys($settings), $owner);
    }

    /**
     * {@inheritdoc}
     */
    public function clear($name, SettingsOwnerInterface $owner = null)
    {
        return $this->set($name, null, $owner);
    }

    /**
     * Sets setting value to private array. Used for settings' batch saving.
     *
     * @param string $name
     * @param mixed $value
     * @param SettingsOwnerInterface|null $owner
     *
     * @return SettingsManager
     */
    private function setWithoutFlush($name, $value, SettingsOwnerInterface $owner = null)
    {
        $this->validateSetting($name, $owner);
        $this->loadSettings($owner);

        if ($owner === null) {
            $this->globalSettings[$name] = $value;
        } else {
            $this->ownerSettings[$owner->getSettingIdentifier()][$name] = $value;
        }

        return $this;
    }

    /**
     * Flushes settings defined by $names to database.
     *
     * @param string|array $names
     * @param SettingsOwnerInterface|null $owner
     *
     * @throws \Dmishh\SettingsBundle\Exception\UnknownSerializerException
     *
     * @return SettingsManager
     */
    private function flush($names, SettingsOwnerInterface $owner = null)
    {
        $names = (array)$names;

        $settings = $this->repository->findBy(
            array(
                'name' => $names,
                'ownerId' => $owner === null ? null : $owner->getSettingIdentifier(),
            )
        );

        // Assert: $settings might be a smaller set than $names

        // For each settings that you are trying to save
        foreach ($names as $name) {
            try {
                $value = $this->get($name, $owner);
            } catch (WrongScopeException $e) {
                continue;
            }

            /** @var Setting $setting */
            $setting = $this->findSettingByName($settings, $name);

            if (!$setting) {
                // if the setting does not exist in DB, create it
                $setting = new Setting();
                $setting->setName($name);
                if ($owner !== null) {
                    $setting->setOwnerId($owner->getSettingIdentifier());
                }
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
    }

    /**
     * Checks that $name is valid setting and it's scope is also valid.
     *
     * @param string $name
     * @param string $group
     *
     * @return SettingsManager
     *
     * @throws \NetBull\SettingsBundle\Exception\UnknownSettingException
     * @throws \NetBull\SettingsBundle\Exception\WrongGroupException
     */
    private function validateSetting($name, string $group = Setting::GROUP_GENERAL)
    {
        // Name validation
        if (!is_string($name) || !array_key_exists($name, $this->settingsConfiguration)) {
            throw new UnknownSettingException($name);
        }

        // Scope validation
        $settingGroup = $this->settingsConfiguration[$name]['group'];
        if ($group === $settingGroup) {
            throw new WrongGroupException($group, $name);
        }

        return $this;
    }

    /**
     * Settings lazy loading.
     *
     * @param SettingsOwnerInterface|null $owner
     *
     * @return SettingsManager
     */
    private function loadSettings(SettingsOwnerInterface $owner = null)
    {
        // Global settings
        if ($this->globalSettings === null) {
            $this->globalSettings = $this->getSettingsFromRepository();
        }

        // User settings
        if ($owner !== null && ($this->ownerSettings === null || !array_key_exists(
                    $owner->getSettingIdentifier(),
                    $this->ownerSettings
                ))
        ) {
            $this->ownerSettings[$owner->getSettingIdentifier()] = $this->getSettingsFromRepository($owner);
        }

        return $this;
    }

    /**
     * Retreives settings from repository.
     *
     * @param SettingsOwnerInterface|null $owner
     *
     * @throws \Dmishh\SettingsBundle\Exception\UnknownSerializerException
     *
     * @return array
     */
    private function getSettingsFromRepository(SettingsOwnerInterface $owner = null)
    {
        $settings = array();

        foreach (array_keys($this->settingsConfiguration) as $name) {
            try {
                $this->validateSetting($name, $owner);
                $settings[$name] = null;
            } catch (WrongScopeException $e) {
                continue;
            }
        }

        /** @var Setting $setting */
        foreach ($this->repository->findBy(
            array('ownerId' => $owner === null ? null : $owner->getSettingIdentifier())
        ) as $setting) {
            if (array_key_exists($setting->getName(), $settings)) {
                $settings[$setting->getName()] = $this->serializer->unserialize($setting->getValue());
            }
        }

        return $settings;
    }
}
