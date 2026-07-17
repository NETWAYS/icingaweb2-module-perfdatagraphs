<?php

namespace Icinga\Module\Perfdatagraphs\Model;

use JsonSerializable;

/**
 * PerfdataSet represents a single chart in the frontend.
 * It in turn can contain several series that are drawn on the chart.
 */
class PerfdataSet implements JsonSerializable
{
     /** @var string The title of this dataset */
    protected string $title;

     /** @var string The unit of this dataset */
    protected string $unit;

     /** @var string The fill of this dataset */
    protected string $fill;

     /** @var string The stroke of this dataset */
    protected string $stroke;

     /** @var string Display this dataset's thresholds or not */
    protected bool $showThresholds = true;

    /** @var iterable The timestamps for this dataset */
    protected iterable $timestamps = [];
    // TODO: iterable might be too broad, we want to be able to use array and SplFixedArray

    /** @var array Associative array of PerfdataSeries for this dataset with their name as key */
    protected array $series = [];

    /**
     * @param string $title
     * @param string $unit
     */
    public function __construct(string $title, string $unit = '')
    {
        $this->title = $title;
        $this->unit = $unit;
    }

    /**
     * jsonSerialize implements JsonSerializable
     *
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        $d = [];

        if (isset($this->title)) {
            $d['title'] = $this->title;
        }

        if (isset($this->unit)) {
            $d['unit'] = $this->unit;
        }

        if (isset($this->fill)) {
            $d['fill'] = $this->fill;
        }

        if (isset($this->stroke)) {
            $d['stroke'] = $this->stroke;
        }

        if (isset($this->showThresholds)) {
            $d['show_thresholds'] = $this->showThresholds;
        }

        if (isset($this->timestamps)) {
            $d['timestamps'] = $this->timestamps;
        }

        if (isset($this->series)) {
            $d['series'] = array_values($this->series);
        }
        return $d;
    }

    /**
     * isEmpty checks if this dataset contains data
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        if (count($this->series) === 0) {
            return true;
        }

        $sets = [];
        foreach ($this->series as $s) {
            $sets[] = $s->isEmpty();
        }

        if (in_array(true, $sets, true)) {
            return true;
        }

        return false;
    }

    /**
     * isValid checks if this dataset contains valid data
     *
     * @return bool
     */
    public function isValid(): bool
    {
        if (empty($this->title)) {
            return false;
        }

        if (count($this->timestamps) === 0) {
            return false;
        }

        if (count($this->series) === 0) {
            return false;
        }

        foreach ($this->series as $s) {
            if (!$s->isValid()) {
                return false;
            }
        }

        return true;
    }

    /**
     * getTitle gets the title of this dataset.
     *
     * @return string The title of this dataset
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * setTitle sets the title of this dataset.
     *
     * @param string $title The title to set
     * @return void
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * Gets the unit of this dataset.
     *
     * @return string The unit
     */
    public function getUnit(): string
    {
        return $this->unit;
    }

    /**
     * setUnit sets the unit for this data series.
     *
     * @param string $unit The unit to set
     * @return void
     */
    public function setUnit(string $unit): void
    {
        $this->unit = $unit;
    }

    /**
     * getFill gets the fill of this dataset.
     *
     * @return string The fill of this dataset
     */
    public function getFill(): string
    {
        return $this->fill;
    }

    /**
     * setFill sets the fill color of the data series.
     *
     * @param string $fill
     * @return void
     */
    public function setFill(string $fill): void
    {
        $this->fill = $fill;
    }

    /**
     * getStroke gets the stroke of this dataset.
     *
     * @return string the stroke of this dataset
     */
    public function getStroke(): string
    {
        return $this->stroke;
    }

    /**
     * setStroke sets the stroke color of the data series.
     *
     * @param string $s
     * @return void
     */
    public function setStroke(string $s): void
    {
        $this->stroke = $s;
    }

    /**
     * getShowThresholds gets the show of this dataset.
     *
     * @return bool value of showThresholds of this dataset
     */
    public function getShowThresholds(): bool
    {
        return $this->showThresholds;
    }

    /**
     * setShowThresholds sets the show color of the data series.
     *
     * @param bool $s
     * @return void
     */
    public function setShowThresholds(bool $s): void
    {
        $this->showThresholds = $s;
    }

    /**
     * getSeries gets the series of this dataset.
     *
     * @return array The series of this dataset
     */
    public function getSeries(): array
    {
        return $this->series;
    }

    /**
     * setSeries overrides the series of this dataset.
     *
     * @param array $series the series for this dataset
     * @return void
     */
    public function setSeries(array $series): void
    {
        $this->series = $series;
    }

    /**
     * addSeries adds a new data series to this dataset.
     * Series are stored by name, this will override a series with same name.
     *
     * @param PerfdataSeries $s
     */
    public function addSeries(PerfdataSeries $s): void
    {
        $this->series[$s->getName()] = $s;
    }

    /**
     * removeSeries removes a data series from this dataset.
     * Series are stored by name, this will remove a series by its name.
     *
     * @param string $name
     */
    public function removeSeries(string $name): void
    {
        if (array_key_exists($name, $this->series)) {
            unset($this->series[$name]);
        }
    }

    /**
     * getTimestamps gets the timestamps of this dataset.
     *
     * @return iterable the timestamps of this dataset
     */
    public function getTimestamps(): iterable
    {
        return $this->timestamps;
    }

    /**
     * setTimestamps sets the timestamps for this dataset.
     *
     * @param iterable $ts
     * @return void
     */
    public function setTimestamps(iterable $ts): void
    {
        $this->timestamps = $ts;
    }

    /**
     * addTimestamp adds a timestamp to this dataset.
     *
     * @param mixed $timestamp The timestamp to add
     * @return void
     */
    public function addTimestamp(mixed $timestamp): void
    {
        $this->timestamps[] = $timestamp;
    }
}
