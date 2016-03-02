<?php

namespace HSLib;

class Cache
{
    /**
     * @var AdvancedCacheInterface
     */
    private $provider;
    /**
     * @var string
     */
    private $table;

    /**
     * @param string $readAddress
     * @param string|null $readSecret
     * @param string $writeAddress
     * @param string|null $writeSecret
     * @param string $db
     * @param string $table
     * @param bool $debug
     */
    public function __construct(
        $readAddress, $readSecret,
        $writeAddress, $writeSecret,
        $db, $table,
        $debug = false
    ) {
        $this->provider = new CacheMultiTable(
            $readAddress, $readSecret,
            $writeAddress, $writeSecret,
            $db, $debug = false
        );

        $this->table = $table;
    }

    /**
     * @inheritdoc
     */
    public function exists($key)
    {
        return $this->provider->exists($this->table, $key);
    }

    /**
     * @inheritdoc
     */
    public function valid($key)
    {
        return $this->provider->valid($this->table, $key);
    }

    /**
     * @inheritdoc
     */
    public function get($key)
    {
        return $this->provider->get($this->table, $key);
    }

    /**
     * @inheritdoc
     */
    public function getMany(array $keys)
    {
        return $this->provider->getMany($this->table, $keys);
    }

    /**
     * @inheritdoc
     */
    public function set($key, $value, $duration = 0)
    {
        return $this->provider->set($this->table, $key, $value, $duration);
    }

    /**
     * @inheritdoc
     */
    public function add($key, $value, $duration = 0)
    {
        return $this->provider->add($this->table, $key, $value, $duration);
    }

    /**
     * @inheritdoc
     */
    public function delete($key)
    {
        return $this->provider->delete($this->table, $key);
    }

    /**
     * @inheritdoc
     */
    public function gc()
    {
        return $this->provider->gc($this->table);
    }

    /**
     * @inheritdoc
     */
    public function flush()
    {
        return $this->provider->flush($this->table);
    }
}
