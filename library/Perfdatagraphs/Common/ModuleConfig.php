<?php

namespace Icinga\Module\Perfdatagraphs\Common;

use Icinga\Module\Perfdatagraphs\Hook\PerfdataSourceHook;

use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Application\Hook;

use Exception;

/**
 * ModuelConfig is a helper class to safely access this module's configuration.
 */
class ModuleConfig
{
    /**
     * getHookbyName loads a Hook via its name
     *
     * @return ?PerfdataSourceHook
     */
    public static function getHookByName(string $name): ?PerfdataSourceHook
    {
        $hooks = Hook::all('perfdatagraphs/PerfdataSource');
        foreach ($hooks as $hook) {
            if ($name === $hook->getName()) {
                return $hook;
            }
        }
        return null;
    }

    /**
     * getHook loads the configured hook from the configuration
     *
     * @return ?PerfdataSourceHook
     */
    public static function getHook(Config $moduleConfig = null): ?PerfdataSourceHook
    {
        // We just default to first hook we find.
        $default = Hook::first('perfdatagraphs/PerfdataSource');

        // Try to load the configuration
        if ($moduleConfig === null) {
            try {
                Logger::debug('Loaded Perfdata Graphs module configuration to get Hook');
                $moduleConfig = Config::module('perfdatagraphs');
            } catch (Exception $e) {
                Logger::error('Failed to load Performance Data Graphs module configuration: %s', $e);
                return $default;
            }
        }

        $configuredHookName = $moduleConfig->get('perfdatagraphs', 'default_backend', 'No such hook');

        $hooks = Hook::all('perfdatagraphs/PerfdataSource');
        // See if we can find the configured hook in the available hooks
        // If not then we return the first we find, which could still be none
        foreach ($hooks as $hook) {
            if ($configuredHookName === $hook->getName()) {
                return $hook;
            }
        }

        return $default;
    }

    /**
     * getConfig loads all configuration options with their defaults.
     *
     * @return array
     */
    public static function getConfig(Config $moduleConfig = null): array
    {
        $default = [
            'cache_lifetime' => 900,
            'default_timerange' => 'PT12H',
        ];

        // Try to load the configuration
        if ($moduleConfig === null) {
            try {
                Logger::debug('Loaded Perfdata Graphs module configuration to get Config');
                $moduleConfig = Config::module('perfdatagraphs');
            } catch (Exception $e) {
                Logger::error('Failed to load Perfdata Graphs module configuration: %s', $e);
                return $default;
            }
        }

        $config = [];
        $config['cache_lifetime'] = (int) $moduleConfig->get('perfdatagraphs', 'cache_lifetime', $default['cache_lifetime']);
        $config['default_timerange'] = $moduleConfig->get('perfdatagraphs', 'default_timerange', $default['default_timerange']);

        return $config;
    }
}
