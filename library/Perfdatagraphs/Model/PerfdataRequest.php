<?php

namespace Icinga\Module\Perfdatagraphs\Model;

/**
 * PerfdataRequest the input for the PerfdataSourceHook
 */
class PerfdataRequest
{
    protected string $hostName;
    protected string $serviceName;
    protected string $checkCommand;
    protected string $duration;
    protected bool $isHostCheck;
    protected array $includeMetrics = [];
    protected array $excludeMetrics = [];

    /**
     * The duration uses the ISO8601 Durations format as string,
     * so that backends can parse it and calculate its date format.
     * It represents the end of the timerange, with now() being the start.
     *
     * Icinga attributes, like host, service, checkcommand should be passed
     * as they are without modification specific to the backend. Each backend
     * can modify these if required (e.g. Graphite special characters to dots).
     *
     * @param string $hostName host name for the performance data query
     * @param string $serviceName service name for the performance data query
     * @param string $checkCommand checkcommand name for the performance data query
     * @param int $checkInterval checkinterval for the performance data query
     * @param string $duration for which to fetch the data for in PHP's DateInterval format (e.g. PT12H, P1D, P1Y)
     * @param bool $isHostCheck is this a Host or Service Check that is requested. Backends queries might differ for these.
     * @param array $includeMetrics a list of metrics that are requested, if not set all available metrics should be returned
     * @param array $excludeMetrics a list of metrics should be excluded from the results, if not set no metrics should be excluded
     */
    public function __construct(
        string $hostName,
        string $serviceName,
        string $checkCommand,
        int $checkInterval,
        string $duration,
        bool $isHostCheck,
        array $includeMetrics = [],
        array $excludeMetrics = []
    ) {
        $this->hostName = $hostName;
        $this->serviceName = $serviceName;
        $this->checkCommand = $checkCommand;
        $this->checkInterval = $checkInterval;
        $this->duration = $duration;
        $this->isHostCheck = $isHostCheck;
        $this->includeMetrics = $includeMetrics;
        $this->excludeMetrics = $excludeMetrics;
    }

    public function getHostname(): string
    {
        return $this->hostName;
    }

    public function getServicename(): string
    {
        return $this->serviceName;
    }

    public function getCheckcommand(): string
    {
        return $this->checkCommand;
    }

    public function getCheckInterval(): int
    {
        return $this->checkInterval;
    }

    public function getDuration(): string
    {
        return $this->duration;
    }

    public function isHostCheck(): bool
    {
        return $this->isHostCheck;
    }

    public function getIncludeMetrics(): array
    {
        return $this->includeMetrics;
    }

    public function getExcludeMetrics(): array
    {
        return $this->excludeMetrics;
    }
}
