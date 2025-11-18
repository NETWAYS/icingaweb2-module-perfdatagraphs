# Writing a Performance Data Backend

In order to write a custom backend for the Icinga Web Module for Performance Data Graphs you need to implement
the PerfdataSource hook provided by this module.

## PerfdataSourceHook

First, you need to create an Icinga Web Module that implements the `PerfdataSourceHook` provided by this module here.

The hook requires the following methods:

- `public function getName(): string;`
  - The `getName()` method returns a descriptive name for the backend. This is used - for example - in the configuration page.

- `public function fetchData(PerfdataRequest $req): PerfdataResponse;`
   - The `fetchData()` method returns the data that is rendered into charts.

For details see: `library/Perfdatagraphs/Hook/PerfdataSourceHook.php`

## Data model

The hook relies on the following data model:

**PerfdataRequest**

This object contains everything a backend needs to fetch data (e.g. host, service, checkcommand).
You can use this object to build a query for actual database that contains the performance data.

**PerfdataResponse**

This object contains the data returned from a backend, which is then rendered into charts.

To represent the performance data is uses:

**PerfdataSet**

This object represents a single chart in the frontend (e.g. `pl` are `rta` for the check_ping are two PerfdataSets).

A PerfdataSet can contain multiple PerfdataSeries.

**PerfdataSeries**

This object represents a single series (y-axis) on the chart (e.g. warning, critical, values).

### PerfdataRequest

The `PerfdataRequest` contains the following data:

* `string $hostName` host name for the performance data query
* `string $serviceName` service name for the performance data query
* `string $checkCommand` checkcommand name for the performance data query
* `bool $isHostCheck` is this a Host or Service Check that is requested, since backends queries might differ.
* `array $includeMetrics` a list of metrics that are requested, if not set all available metrics should be returned
* `array $excludeMetrics` a list of metrics should be excluded from the results, if not set no metrics should be excluded
* `string $duration` duration for which to fetch the data for in PHP's [DateInterval](https://www.php.net/manual/en/class.dateinterval.php) format (e.g. PT12H, P1D, P1Y)

ISO8601 durations are used because:

1. it provides a simple and parsable format to send via URL parameters
2. PHP offers a native function to parse the format
3. each backend has different requirements for the time range format, ISO8601 durations provide common ground.

The duration is used to calculate the time range that the user requested. The current timestamp as a starting point is implicit.

### PerfdataResponse

The `PerfdataResponse` is a `JsonSerializable` that we use to render the charts.

This object one or more `PerfdataSet`

It can also include a list of errors, which are used to communicate issues to the user.

Each `PerfdataSet` must contain the timestamps used for the x-axis.
Timestamps are a list of UNIX epoch integers (in seconds).

A `PerfdataSet` must contain at least one `PerfdataSeries` with values for the y-axis.
These are basically a list of integers or floats.

A `PerfdataSet` may contain additional `PerfdataSeries` for the y-axis (e.g. warning or critical series).

Example:

```php
<?php

namespace Icinga\Module\Perfdatagraphsexample\ProvidedHook\PerfdataGraphs;

use Icinga\Module\Perfdatagraphs\Hook\PerfdataSourceHook;

use Icinga\Module\Perfdatagraphs\Model\PerfdataRequest;
use Icinga\Module\Perfdatagraphs\Model\PerfdataResponse;
use Icinga\Module\Perfdatagraphs\Model\PerfdataSet;
use Icinga\Module\Perfdatagraphs\Model\PerfdataSeries;

class PerfdataSource extends PerfdataSourceHook
{
    public function getName(): string
    {
        return 'Example';
    }

    public function fetchData(PerfdataRequest $req): PerfdataResponse
    {
        $response = new PerfdataResponse();

        $dataset = new PerfdataSet('latency', 'seconds');

        $dataset->setTimestamps([1763400100, 1763400200, 1763400300]);

        $values = new PerfdataSeries('value', [1, 5, 3]);
        $dataset->addSeries($values);

        $warnings = new PerfdataSeries('warning', [4, 4, 4]);
        $dataset->addSeries($warnings);

        $response->addDataset($dataset);

        return $response;
    }
}
```
