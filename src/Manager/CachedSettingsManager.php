<?php

namespace NetBull\SettingsBundle\Manager;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

class CachedSettingsManager
{
    const PREFIX = 'netbull_settings_%s_%s';

    /**
     * @var CacheItemPoolInterface
     */
    private $storage;

    /**
     * @var SettingsManagerInterface
     */
    private $settingsManager;

    /**
     * @var int
     */
    private $cacheLifeTime;

    /**
     * @param SettingsManagerInterface $settingsManager
     * @param CacheItemPoolInterface $storage
     * @param $cacheLifeTime
     */
    public function __construct(SettingsManagerInterface $settingsManager, CacheItemPoolInterface $storage, $cacheLifeTime)
    {
        $this->settingsManager = $settingsManager;
        $this->storage = $storage;
        $this->cacheLifeTime = $cacheLifeTime;
    }

    /**
     * @param string $name
     * @param string $group
     * @param null $default
     * @return mixed
     */
    public function get(string $name, string $group, $default = null)
    {
        if (null !== $cached = $this->fetchFromCache($name, $group)) {
            return $cached;
        }

        $value = $this->settingsManager->get($name, $group, $default);
        $this->storeInCache($name, $value, $group);

        return $value;
    }

    /**
     * @param string $group
     * @return array|mixed|null
     */
    public function all(string $group)
    {
        if (null !== $cached = $this->fetchFromCache(null, $group)) {
            return $cached;
        }

        $value = $this->settingsManager->all($group);
        $this->storeInCache(null, $value, $group);

        return $value;
    }

    /**
     * @param string $name
     * @param $value
     * @param string $group
     * @return SettingsManagerInterface
     */
    public function set(string $name, $value, string $group): SettingsManagerInterface
    {
        $this->invalidateCache($name, $group);
        $this->invalidateCache(null, $group);

        return $this->settingsManager->set($name, $value, $group);
    }

    /**
     * @param array $settings
     * @param string $group
     * @return SettingsManagerInterface
     */
    public function setMany(array $settings, string $group): SettingsManagerInterface
    {
        foreach ($settings as $key => $value) {
            $this->invalidateCache($key, $group);
        }
        $this->invalidateCache(null, $group);

        return $this->settingsManager->setMany($settings, $group);
    }

    /**
     * @param string $name
     * @param string $group
     * @return SettingsManagerInterface
     */
    public function clear(string $name, string $group): SettingsManagerInterface
    {
        $this->invalidateCache($name, $group);
        $this->invalidateCache(null, $group);

        return $this->settingsManager->clear($name, $group);
    }

    /**
     * @param string $name
     * @param string $group
     * @return bool TRUE if the cache entry was successfully deleted, FALSE otherwise.
     */
    protected function invalidateCache(string $name, string $group): bool
    {
        try {
            return $this->storage->deleteItem($this->getCacheKey($name, $group));
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * @param string $name
     * @param string $group
     * @return mixed|null if nothing was found in cache
     */
    protected function fetchFromCache(string $name, string $group)
    {
        $cacheKey = $this->getCacheKey($name, $group);

        try {
            return $this->storage->getItem($cacheKey)->get();
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param string $group
     * @return bool TRUE if the entry was successfully stored in the cache, FALSE otherwise.
     */
    protected function storeInCache(string $name, $value, string $group): bool
    {
        try {
            $item = $this->storage->getItem($this->getCacheKey($name, $group))
                ->set($value)
                ->expiresAfter($this->cacheLifeTime);
        } catch (InvalidArgumentException $e) {
            return false;
        }

        return $this->storage->save($item);
    }

    /**
     * @param string $key
     * @param string $group
     * @return string
     */
    protected function getCacheKey(string $key, string $group): string
    {
        return sprintf(self::PREFIX, $group, $key);
    }
}
