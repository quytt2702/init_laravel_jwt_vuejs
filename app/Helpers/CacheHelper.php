<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class CacheHelper
{
    /**
     * @var Repository|\Illuminate\Cache\Repository
     */
    private $cache;

    /**
     * @var string
     */
    private $driver;

    /**
     * CacheHelper constructor.
     *
     * @param null $driver
     */
    public function __construct($driver = null)
    {
        $this->setDriver($driver);
    }

    /**
     * Refresh
     *
     * @return $this
     */
    public function refresh()
    {
        $this->setDriver(config('cache.default'));

        return $this;
    }

    /**
     * Get cache
     *
     * @return Repository|\Illuminate\Cache\Repository
     */
    public function cache()
    {
        return $this->cache;
    }

    /**
     * Get driver
     *
     * @return string
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Set driver
     *
     * @param $driver
     *
     * @return $this
     */
    public function setDriver($driver)
    {
        $this->driver = empty($driver)
            ? config('cache.default')
            : $driver;

        $this->cache = Cache::store($this->driver);

        return $this;
    }

    /**
     * Detect and get data from cache
     *
     * @param $cacheKey
     *
     * @return bool|mixed
     */
    public function hasData($cacheKey)
    {
        if ($this->cache->has($cacheKey) && $cache = $this->getData($cacheKey)) {
            return $cache;
        } else {
            return false;
        }
    }

    /**
     * Get data from cache
     *
     * @param string $cacheKey
     * @param null   $default
     *
     * @return mixed
     */
    public function getData(string $cacheKey, $default = null)
    {
        return $this->cache->get($cacheKey, $default);
    }

    /**
     * Pull data from cache
     *
     * @param string $cacheKey
     * @param null   $default
     *
     * @return mixed
     */
    public function pullData(string $cacheKey, $default = null)
    {
        return $this->cache->pull($cacheKey, $default);
    }

    /**
     * Save data into cache
     *
     * @param string      $cacheKey
     * @param mixed       $data
     * @param Carbon|null $cacheTime
     *
     * @return mixed
     */
    public function save(string $cacheKey, $data, Carbon $cacheTime = null)
    {
        $this->cache->set($cacheKey, $data, $cacheTime);

        return $this->getData($cacheKey);
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result
     *
     * @param string      $cacheKey
     * @param callback    $closure
     * @param Carbon|null $cacheTime
     *
     * @return bool|mixed|null
     */
    public function remember($cacheKey, $closure, ?Carbon $cacheTime = null)
    {
        if ($cache = $this->hasData($cacheKey)) {
            return $cache;
        } else {
            return $this->save($cacheKey, call_user_func($closure), $cacheTime);
        }
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result forever
     *
     * @param $cacheKey
     * @param $closure
     *
     * @return bool|mixed|null
     */
    public function rememberForever($cacheKey, $closure)
    {
        if ($cache = $this->hasData($cacheKey)) {
            return $cache;
        } else {
            return $this->cache->rememberForever($cacheKey, call_user_func($closure));
        }
    }

    /**
     * Remove an item from the cache.
     *
     * @param $cacheKey
     */
    public function clear($cacheKey = null)
    {
        if ($cacheKey) {
            $this->cache->delete($cacheKey);
        } else {
            $this->cache->clear();
        }
    }

    /**
     * Detect and get data from cache with tag
     *
     * @param array|string $tags
     * @param              $cacheKey
     *
     * @return mixed
     * @throws \Exception
     */
    public function hasDataWithTags($tags, $cacheKey)
    {
        if (!$this->cache->supportsTags()) {
            throw new \Exception(
                sprintf(
                    'Cache tags are not supported when using the "%s" driver',
                    $this->driver
                ),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        if ($cache = $this->getDataWithTags($tags, $cacheKey)) {
            return $cache;
        } else {
            return false;
        }
    }

    /**
     * Get data from cache with tags
     *
     * @param mixed  $tags
     * @param string $cacheKey
     * @param null   $default
     *
     * @return mixed
     */
    public function getDataWithTags($tags, string $cacheKey, $default = null)
    {
        return $this->cache->tags((array)$tags)->get($cacheKey, $default);
    }

    /**
     * Pull data from cache with tags
     *
     * @param mixed  $tags
     * @param string $cacheKey
     * @param null   $default
     *
     * @return mixed
     */
    public function pullDataWithTags($tags, string $cacheKey, $default = null)
    {
        return $this->cache->tags((array)$tags)->pull($cacheKey, $default);
    }

    /**
     * Save data into cache with tag
     *
     * @param array|string $tags
     * @param string       $cacheKey
     * @param mixed        $data
     * @param Carbon|null  $cacheTime
     *
     * @return mixed
     * @throws \Exception
     */
    public function saveWithTags($tags, string $cacheKey, $data, Carbon $cacheTime = null)
    {
        if (!$this->cache->supportsTags()) {
            throw new \Exception(
                sprintf(
                    'Cache tags are not supported when using the "%s" driver',
                    $this->driver
                ),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $this->cache->tags($tags)
            ->set($cacheKey, $data, $cacheTime);

        return $this->getDataWithTags($tags, $cacheKey);
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result with tag
     *
     * @param array|string $tags
     * @param              $cacheKey
     * @param              $closure
     * @param Carbon       $cacheTime
     *
     * @return mixed
     * @throws \Exception
     */
    public function rememberWithTags($tags, $cacheKey, $closure, ?Carbon $cacheTime = null)
    {
        if (!$this->cache->supportsTags()) {
            throw new \Exception(
                sprintf(
                    'Cache tags are not supported when using the "%s" driver',
                    $this->driver
                ),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        if ($cache = $this->hasDataWithTags($tags, $cacheKey)) {
            return $cache;
        } else {
            return $this->saveWithTags($tags, $cacheKey, call_user_func($closure), $cacheTime);
        }
    }

    /**
     * Remove/Clear item from the cache Tag.
     *
     * @param array|string $tags
     * @param null|string  $cacheKey
     *
     * @throws \Exception
     */
    public function clearWithTags($tags, string $cacheKey = null)
    {
        if (!$this->cache->supportsTags()) {
            throw new \Exception(
                sprintf(
                    'Cache tags are not supported when using the "%s" driver',
                    $this->driver
                ),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        if (is_null($cacheKey)) {
            $this->cache->tags($tags)->clear();
        } else {
            $this->cache->tags($tags)->delete($cacheKey);
        }
    }

    /**
     * Build cache key from params
     *
     * @param array $params
     *
     * @return string
     */
    public function buildCacheKey($key, array $params = []): string
    {
        $cacheKey = [];

        if (!is_null($key)) {
            $cacheKey[] = $key;
        }

        if ($params) {
            $params = Arr::sortRecursive($params);

            foreach ($params as $index => $param) {
                if (isset($param)) {
                    if (is_array($param)) {
                        $param = nested_to_single($param);
                        $param = implode('_', $param);
                    }

                    $cacheKey[] = "{$index}_{$param}";
                }
            }
        }

        return implode(',', $cacheKey);
    }
}
