# Modifying Performance Data

The module provides a hook to modify data after it has been fetched from the backend.
This can be useful if you have special cases that the module does not cover.

## PerfdataPrerenderHook

You need to create an Icinga Web Module that implements the `PerfdataPrerenderHook` provided by this module here.

The hook requires the following methods:

- `public function transform(PerfdataResponse $res): PerfdataResponse;`

**Note that**, the hook receives a reference to the PerfdataResponse object.

### Example

Example implementation of the PerfdataPrerenderHook that calculates a rate from a counter:

```php
<?php

namespace Icinga\Module\Example\ProvidedHook\Perfdatagraphs;

use Icinga\Module\Perfdatagraphs\Hook\PerfdataPrerenderHook;
use Icinga\Module\Perfdatagraphs\Model\PerfdataResponse;
use Icinga\Module\Perfdatagraphs\Model\PerfdataSet;
use Icinga\Module\Perfdatagraphs\Model\PerfdataSeries;

class PerfdataPrerender extends PerfdataPrerenderHook
{
    public function transform(PerfdataResponse $response): PerfdataResponse
    {
        $c = $response->getDataset('uptime');
        if (empty($c)) {
            return $response;
        }

        $rate = $this->calculateRates($c->getTimestamps(), $c->getSeriesByName('value')->getValues());

        $rateSet = new PerfdataSet('rate');
        $rateSet->setTimestamps($rate['timestamps']);
        $rateSeries = new PerfdataSeries('value', $rate['rates']);
        $rateSet->addSeries($rateSeries);
        $response->addDataset($rateSet);

        return $response;
    }

    protected function calculateRates($timestamps, $values): array
    {
        $count = count($timestamps);
        if ($count !== count($values) || $count < 2) {
            return [];
        }

        $rates = [];
        $ts = [];

        for ($i = 1; $i < $count; $i++) {
            $dt = $timestamps[$i] - $timestamps[$i - 1];
            if ($dt <= 0) {
                continue;
            }

            $delta = $values[$i] - $values[$i - 1];
            if ($delta < 0) {
                $delta = $values[$i];
            }

            $ts[] = $timestamps[$i];
            $rates[] = $delta / $dt;
        }

        return ['timestamps' => $ts, 'rates' => $rates];
    }
}
```
