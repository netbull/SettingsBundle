<?php

namespace NetBull\SettingsBundle\Manager;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

/**
 * @author Tobias Nyholm
 */
class CachedSettingsManager implements SettingsManagerInterface
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
     * CachedSettingsManager constructor.
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
     * @param null $default
     * @return mixed
     */
    public function get($name, $default = null)
    {
        if (null !== $cached = $this->fetchFromCache($name)) {
            return $cached;
        }

        $value = $this->settingsManager->get($name, $default);
        $this->storeInCache($name, $value);

        return $value;
    }

    /**
     * @return array|mixed
     */
    public function all()
    {
        if (null !== $cached = $this->fetchFromCache(null)) {
            return $cached;
        }

        $value = $this->settingsManager->all();
        $this->storeInCache(null, $value);

        return $value;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return SettingsManagerInterface
     */
    public function set($name, $value)
    {
        $this->invalidateCache($name);
        $this->invalidateCache(null);

        return $this->settingsManager->set($name, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function setMany(array $settings)
    {
        foreach ($settings as $key => $value) {
            $this->invalidateCache($key);
        }
        $this->invalidateCache(null);

        return $this->settingsManager->setMany($settings);
    }

    /**
     * {@inheritdoc}
     */
    public function clear($name)
    {
        $this->invalidateCache($name);
        $this->invalidateCache(null);

        return $this->settingsManager->clear($name);
    }

    /**
     * @param $name
     * @return bool TRUE if the cache entry was successfully deleted, FALSE otherwise.
     */
    protected function invalidateCache($name)
    {
        try {
            return $this->storage->deleteItem($this->getCacheKey($name));
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * @param $name
     * @return mixed|null
     */
    protected function fetchFromCache($name)
    {
        $cacheKey = $this->getCacheKey($name);

        try {
            return $this->storage->getItem($cacheKey)->get();
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * @param $name
     * @param $value
     * @return bool
     */
    protected function storeInCache($name, $value)
    {
        try {
            $item = $this->storage->getItem($this->getCacheKey($name))
                ->set($value)
                ->expiresAfter($this->cacheLifeTime);
        } catch (InvalidArgumentException $e) {
            return null;
        }

        if (!$item) {
            return null;
        }

        return $this->storage->save($item);
    }

    /**
     * @param $key
     * @return string
     */
    protected function getCacheKey($key)
    {
        return sprintf(self::PREFIX, $key);
    }
}
