<?php

namespace Icinga\Module\Perfdatagraphs\Common;

use Icinga\Module\Perfdatagraphs\Model\PerfdataRequest;
use Icinga\Module\Perfdatagraphs\Model\PerfdataResponse;

use Icinga\Application\Benchmark;
use Icinga\Application\Logger;

use Exception;

/**
 * PerfdataSource contains everything related to fetching and transforming data.
 * The idea is that you use this behind the scenes to get the data.
 */
class PerfdataSource
{
    // This Module's config
    protected $config;

    // This Module's FileCache
    protected $cache;

    // The Hook to use
    protected $hook;

    /**
     * @param $config the module's configuration
     * @param $hook the backend hook to use
     */
    public function __construct($config, $hook)
    {
        $this->config = $config;
        $this->hook = $hook;
        $this->cache = PerfdataCache::instance('perfdatagraphs');
    }

    /**
     * getDataFromCache returns the wanted data from the module's FileCache if present.
     *
     * @param string $cacheKey The key for this cache
     * @param int $duration How long the cached data is valid
     */
    public function getDataFromCache(string $cacheKey, int $duration): PerfdataResponse|false
    {
        // Check the cache for existing data
        if ($cacheKey !== null && $duration > 0) {
            if ($this->cache->has($cacheKey, time() - $duration)) {
                Logger::debug('Found data in cache for ' . $cacheKey);
                return unserialize($this->cache->get($cacheKey));
            }
        }

        Logger::debug('Found no data in cache for ' . $cacheKey);

        return false;
    }

    /**
     * storeDataToCache stores the given data to the module's FileCache
     *
     * @param string $cacheKey The key for this cache
     * @param PerfdataResponse $data The list of data to store. We mainly use this to store the JSON encoded datasets,
     * so that we don't have to encode them again. There might still be a bit of overhead with the serialize().
     */
    public function storeDataToCache(string $cacheKey, PerfdataResponse $data): void
    {
        Logger::debug('Storing data in cache for ' . $cacheKey);
        $this->cache->store($cacheKey, serialize($data));
    }

    /**
     */
    public function fetchViaHook(PerfdataRequest $request): PerfdataResponse
    {
        Benchmark::measure('Fetching performance data');
        $response = new PerfdataResponse();

        try {
            $response = $this->hook->fetchData($request);
        } catch (Exception $e) {
            $err = sprintf('Failed to call PerfdataSource hook: %s', $e->getMessage());
            Logger::error($err);
            $response->addError($err);
            return $response;
        } finally {
            Benchmark::measure('Fetched performance data');
        }

        return $response;
    }

    /**
     * fetchDataViaHook calls the configured PerfdataSourceHook to fetch the perfdata from the backend.
     *
     * @param PerfdataRequest $request Request we use to fetch the data for
     * @param array $customVarsMetrics customvars for the metrics that are then merged
     *
     * @return PerfdataResponse
     */
    public function fetch(PerfdataRequest $request, array $customVarsMetrics): PerfdataResponse
    {
        // TODO: We could use the HTTP Cache-Control Header to invalidate cache
        $cacheDurationInSeconds = $this->config['cache_lifetime'];
        $h = $request->isHostCheck() ? 'true': 'false';
        $cacheKey = base64_encode($request->getHostname() . $request->getServicename() . $request->getCheckcommand() . $request->getDuration() . $h);

        // Get data from cache if it is available
        $response = $this->getDataFromCache($cacheKey, $cacheDurationInSeconds);

        if (!$response) {
            $response = $this->fetchViaHook($request);
            // Merge everything into the response.
            // We could have also done this browser-side but decided to do this here
            // because of simpler testability.
            $response->mergeCustomVars($customVarsMetrics);
            $this->storeDataToCache($cacheKey, $response);
        }

        return $response;
    }
}
