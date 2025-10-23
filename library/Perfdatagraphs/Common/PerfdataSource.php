<?php

namespace Icinga\Module\Perfdatagraphs\Common;

use Icinga\Module\Perfdatagraphs\Model\PerfdataRequest;
use Icinga\Module\Perfdatagraphs\Model\PerfdataResponse;
use Icinga\Module\Perfdatagraphs\Common\ModuleConfig;
use Icinga\Module\Perfdatagraphs\ProvidedHook\Icingadb\IcingadbSupport;

use Icinga\Module\Perfdatagraphs\Icingadb\CustomVarsHelper as IcinaDBCVH;
use Icinga\Module\Perfdatagraphs\Ido\CustomVarsHelper as IdoCVH;

use Icinga\Application\Modules\Module;
use Icinga\Application\Logger;

use Exception;

/**
 * PerfdataSource contains everything related to fetching and transforming data.
 * The idea is that you use this behind the scenes to get the data.
 */
class PerfdataSource
{
    // This Module's configuration
    protected $config;

    // This Module's FileCache
    protected $cache;

    /**
     * @param $config the module's configuration
     */
    public function __construct($config)
    {
        $this->config = $config;

        $this->cache = PerfdataCache::instance('perfdatagraphs');
    }

    /**
     * getDataFromCache returns the wanted data from the module's FileCache if present.
     *
     * @param string $cacheKey The key for this cache
     * @param int $duration How long the cached data is valid
     */
    public function getDataFromCache(string $cacheKey, int $duration): array|false
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
     * @param array $datasets The list of data to store. We mainly use this to store the JSON encoded datasets,
     * so that we don't have to encode them again. There might still be a bit of overhead with the serialize().
     */
    public function storeDataToCache(string $cacheKey, array $datasets): void
    {
        Logger::debug('Storing data in cache for ' . $cacheKey);
        $this->cache->store($cacheKey, serialize($datasets));
    }

    /**
     * fetchDataViaHook calls the configured PerfdataSourceHook to fetch the perfdata from the backend.
     *
     * @param string $host Name of the host
     * @param string $service Name of the service
     * @param string $checkcommand Name of the checkcommand
     * @param string $duration Duration for which to fetch the data
     * @param bool $isHostCheck Is this a Host check
     *
     * @return PerfdataResponse
     */
    public function fetchDataViaHook(string $host, string $service, string $checkcommand, string $duration, bool $isHostCheck): PerfdataResponse
    {
        $response = new PerfdataResponse();

        if (Module::exists('icingadb') && IcingadbSupport::useIcingaDbAsBackend()) {
            Logger::debug('Used IcingaDB as database backend');
            $cvh = new IcinaDBCVH();
        } else {
            Logger::debug('Used IDO as database backend');
            $cvh = new IdoCVH();
        }

        // Get the object so that we can get its custom variables.
        $object = $cvh->getObjectFromString($host, $service, $isHostCheck);

        // If there's no object we can just stop here.
        if (empty($object)) {
            Logger::warning('Failed to find object from given host-service strings');
            return $response;
        }

        // Load the custom variables for the metrics to include and exclude
        $customvars = $cvh->getPerfdataGraphsConfigForObject($object);

        $metricsToInclude = [];
        if ($customvars[$cvh::CUSTOM_VAR_CONFIG_INCLUDE] ?? false) {
            $metricsToInclude = $customvars[$cvh::CUSTOM_VAR_CONFIG_INCLUDE];
        }

        $metricsToExclude = [];
        if ($customvars[$cvh::CUSTOM_VAR_CONFIG_EXCLUDE] ?? false) {
            $metricsToExclude = $customvars[$cvh::CUSTOM_VAR_CONFIG_EXCLUDE];
        }

        // If the object wants the data from a custom backend
        if ($customvars[$cvh::CUSTOM_VAR_CONFIG_BACKEND] ?? false) {
            $hook = ModuleConfig::getHookByName($customvars[$cvh::CUSTOM_VAR_CONFIG_BACKEND]);
        } else {
            /** @var PerfdataSourceHook $hook */
            $hook = ModuleConfig::getHook();
        }

        // If there is no hook configured we return here.
        if (empty($hook)) {
            Logger::error('No valid PerfdataSource hook configured');
            $response->addError('No valid PerfdataSource hook configured');
            return $response;
        }

        // Create a new PerfdataRequest with the given parameters and custom variables
        $request = new PerfdataRequest($host, $service, $checkcommand, $duration, $isHostCheck, $metricsToInclude, $metricsToExclude);

        // Try to fetch the data with the hook.
        try {
            $response = $hook->fetchData($request);
        } catch (Exception $e) {
            $err = sprintf('Failed to call PerfdataSource hook: %s', $e->getMessage());
            Logger::error($err);
            $response->addError($err);

            return $response;
        }

        // Merge everything into the response.
        // We could have also done this browser-side but decided to do this here
        // because of simpler testability.
        $customVarsMetrics = $cvh->getPerfdataGraphsMetricsForObject($object);

        $response->mergeCustomVars($customVarsMetrics);

        // If the a dataset is set to be highlighted, move it at the top of the array.
        if ($customvars[$cvh::CUSTOM_VAR_CONFIG_HIGHLIGHT] ?? false) {
            $response->setDatasetToHighlight($customvars[$cvh::CUSTOM_VAR_CONFIG_HIGHLIGHT] ?? '');
        }

        return $response;
    }
}
