<?php

namespace Icinga\Module\Perfdatagraphs\Model;

use JsonSerializable;

/**
 * PerfdataResponse is what the PerfdataSourceHook returns
 * and what we pass to the module.js
 */
class PerfdataResponse implements JsonSerializable
{
    // List of PerfdataSets with their title as key
    // ['rta'] = <PerfdataSet>
    protected array $data = [];

    // List of error if there are any
    protected array $errors = [];

    /**
     * getErrors returns the errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * addError adds an error message to this object.
     * @param string $e error message to append
     */
    public function addError(string $e): void
    {
        $this->errors[] = $e;
    }

    /**
     * hasErrors checks if this response has any errors.
     */
    public function hasErrors(): bool
    {
        if (count($this->errors) > 0) {
            return true;
        }
        return false;
    }

    /**
     * isValid checks if this response contains data
     */
    public function isEmpty(): bool
    {
        return count($this->data) === 0;
    }

    /**
     * isValid checks if this response contains valid data
     */
    public function isValid(): bool
    {
        foreach ($this->data as $dataset) {
            if (!$dataset->isValid()) {
                return false;
            }
        }

        return true;
    }

    /**
     * getDatasets returns the datasets.
     *
     * @return array
     */
    public function getDatasets(): array
    {
        return $this->data;
    }

    /**
     * getDataset returns a dataset by its name.
     *
     * @param string $title the dataset to return
     * @return PerfdataSet
     */
    public function getDataset(string $title): ?PerfdataSet
    {
        if (array_key_exists($title, $this->data)) {
            return $this->data[$title];
        }
    }

    /**
     * addDataset adds a new PerfdataSet (which respresents a single chart in the frontend).
     * @param PerfdataSet $ds the dataset to add
     */
    public function addDataset(PerfdataSet $ds): void
    {
        $this->data[$ds->getTitle()] = $ds;
    }

    public function jsonSerialize(): mixed
    {
        $d = [];

        if (isset($this->errors)) {
            $d['errors'] = $this->errors;
        }
        if (isset($this->data)) {
            $d['data'] = array_values($this->data);
        }

        return $d;
    }

    /**
     * getCustomvarForDataSet uses fnmatch to return the customvars for a given dataset.
     * array_find would have been nice but only in PHP 8.4.
     *
     * @param array $customvars array of all customvars
     * @param string $datasetTitle the title of the dataset to find customvars for
     * @return ?array customvars for the matching dataset
     */
    protected function getCustomvarForDataSet(array $customvars, string $datasetTitle): ?array
    {
        foreach (array_keys($customvars) as $cvarTitle) {
            if (fnmatch($cvarTitle, $datasetTitle)) {
                return $customvars[$cvarTitle];
            }
        }

        return null;
    }

    /**
     * mergeCustomVars merges the performance data with the custom vars,
     * so that each series receives its corresponding vars.
     * CustomVars override data in the PerfdataSet.
     *
     * We could have also done this browser-side but decided to do this here
     * because of simpler testability. We could change that if browser-side merging
     * is more performant.
     *
     * If the functionality remains here, we should optimize if for performance.
     *
     * @param array $customvars The custom variables for the given object
     */
    public function mergeCustomVars(array $customvars): void
    {
        // If we don't have any custom vars simply return
        if (empty($customvars)) {
            return;
        }

        // If we don't have any data simply return
        if (empty($this->data)) {
            return;
        }

        foreach ($this->data as $dkey => $dataset) {
            $cvar = $this->getCustomvarForDataSet($customvars, $dkey);

            // If we don't have any customvar for the given dataset, skip
            if (empty($cvar)) {
                continue;
            }

            // Override the elements if there are values for them
            if (isset($cvar['unit'])) {
                $this->data[$dkey]->setUnit($cvar['unit']);
            }
            if (isset($cvar['fill'])) {
                $this->data[$dkey]->setFill($cvar['fill']);
            }
            if (isset($cvar['stroke'])) {
                $this->data[$dkey]->setStroke($cvar['stroke']);
            }
        }
    }

    /**
     * setDatasetToHighlight sets a given dataset at the beginning of
     * the data array, so that it is rendered first.
     * @param string $name the name of the dataset to highlight
     */
    public function setDatasetToHighlight(string $name): void
    {
        if (!array_key_exists($name, $this->data)) {
            return;
        }

        $value = [$name => $this->data[$name]];
        unset($this->data[$name]);

        $this->data = $value + $this->data;
    }
}
