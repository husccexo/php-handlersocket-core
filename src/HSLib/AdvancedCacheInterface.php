<?php

namespace HSLib;

interface AdvancedCacheInterface
{
    /**
     * Checks whether a specified key exists in the cache.
     *
     * @param string $group group of cache
     * @param string $key a key identifying the cached value.
     * @return bool true if a value exists in cache, false if the value is not in the cache.
     */
    public function exists($group, $key);

    /**
     * Checks whether a specified key exists in the cache and not expired yet.
     * This can be faster than getting the value from the cache if the data is big.
     *
     * @param string $group group of cache
     * @param string $key a key identifying the cached value.
     * @return bool true if a value exists in cache, false if the value is not in the cache or expired.
     */
    public function valid($group, $key);

    /**
     * Retrieves a value from cache with a specified group of cache and key.
     *
     * @param string $group group of cache
     * @param string $key a unique key identifying the cached value
     * @return string|null the value stored in cache, null if the value is not in the cache or expired.
     */
    public function get($group, $key);

    /**
     * Retrieves multiple values from cache with the specified group of cache and keys.
     *
     * @param string $group group of cache
     * @param string[] $keys a list of keys identifying the cached values
     * @return string[] a list of cached values indexed by the keys
     */
    public function getMany($group, array $keys);

    /**
     * Stores a value identified by group of cache and a key in cache.
     *
     * @param string $group group of cache
     * @param string $key the key identifying the value to be cached
     * @param string $value the value to be cached
     * @param int $duration the number of seconds in which the cached value will expire. 0 means never expire.
     * @return bool true if the value is successfully stored into cache, false otherwise
     */
    public function set($group, $key, $value, $duration = 0);

    /**
     * Stores a value identified by a key into cache if the cache does not contain this key.
     *
     * @param string $group group of cache
     * @param string $key the key identifying the value to be cached
     * @param string $value the value to be cached
     * @param int $duration the number of seconds in which the cached value will expire. 0 means never expire.
     * @return bool true if the value is successfully stored into cache, false otherwise
     */
    public function add($group, $key, $value, $duration = 0);

    /**
     * Deletes a value with the specified group and key from cache
     *
     * @param string $group group of cache
     * @param string $key the key of the value to be deleted
     * @return bool if no error happens during deletion
     */
    public function delete($group, $key);

    /**
     * Removes the expired data values.
     *
     * @param string $group group of cache
     * @return bool
     */
    public function gc($group);

    /**
     * Deletes all values from cache.
     *
     * @param string $group group of cache
     * @return bool
     */
    public function flush($group);
}
