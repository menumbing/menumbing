<?php

declare(strict_types=1);

namespace Menumbing\GraphQL\Cache;

use Psr\SimpleCache\CacheInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class MemoryCache implements CacheInterface
{
    protected static array $data = [];

    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            return static::$data[$key];
        }

        return $default;
    }

    public function set($key, $value, $ttl = null)
    {
        static::$data[$key] = $value;

        return true;
    }

    public function delete($key)
    {
        if ($this->has($key)) {
            unset(static::$data[$key]);

            return true;
        }

        return false;
    }

    public function clear()
    {
        static::$data = [];

        return true;
    }

    public function getMultiple($keys, $default = null)
    {
        $data = [];

        foreach ((array) $keys as $key) {
            $data[$key] = $this->get($key, $default);
        }

        return $data;
    }

    public function setMultiple($values, $ttl = null)
    {
        static::$data = array_merge(static::$data, (array) $values);

        return true;
    }

    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function has($key)
    {
        return array_key_exists($key, static::$data);
    }
}
