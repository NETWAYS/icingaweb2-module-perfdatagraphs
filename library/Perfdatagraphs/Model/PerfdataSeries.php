<?php

namespace Icinga\Module\Perfdatagraphs\Model;

use JsonSerializable;
use SplFixedArray;

/**
 * PerfdataSeries represents a single series (y-axis) on the chart.
 */
class PerfdataSeries implements JsonSerializable
{
     /** @var string The name for this series */
    protected string $name;

     /** @var array|\SplFixedArray The values for this series */
    protected array|\SplFixedArray $values;

    /**
     * @param string $name
     * @param array|\SplFixedArray $values
     */
    public function __construct(string $name, array|\SplFixedArray $values = [])
    {
        $this->name = $name;
        $this->values = $values;
    }

    /**
     * getName returns the name for the series
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * setName sets the name for the series
     *
     * @param string $name
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * getValues returns the values for the series
     *
     * @return array|\SplFixedArray
     */
    public function getValues(): array|\SplFixedArray
    {
        return $this->values;
    }

    /**
     * addValue adds a value to the series
     *
     * @param mixed $value
     * @return void
     */
    public function addValue(mixed $value): void
    {
        $this->values[] = $value;
    }

    /**
     * setValues sets the values for the series
     *
     * @param array|\SplFixedArray $values
     * @return void
     */
    public function setValues(array|\SplFixedArray $values): void
    {
        $this->values = $values;
    }

    /**
     * jsonSerialize implements JsonSerializable
     *
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->name,
            'values' => $this->values,
        ];
    }

    /**
     * isEmpty checks if this series contains data and if the data is not null
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        if (count($this->values) === 0) {
            return true;
        }

        // Keeping it simply since values are an iterable (e.g. SplFixedArray)
        foreach ($this->values as $v) {
            if (!is_null($v)) {
                return false;
            }
        }

        return true;
    }

    /**
     * isValid checks if this series contains valid data
     *
     * @return bool
     */
    public function isValid(): bool
    {
        if ($this->name === '') {
            return false;
        }

        if (count($this->values) === 0) {
            return false;
        }

        return true;
    }
}
