<?php

namespace Icinga\Module\Perfdatagraphs\Hook;

use Icinga\Module\Perfdatagraphs\Model\PerfdataResponse;

/**
 * PerfdataPrerenderHook can be used to modify the PerfdataResponse.
 * It is called before the data is being rendered.
 */
abstract class PerfdataPrerenderHook
{
    /**
     * transform is used to modify the PerfdataResponse
     *
     * @param PerfdataResponse $response The response to transform
     * @return PerfdataResponse
     */
    abstract public function transform(PerfdataResponse $response): PerfdataResponse;
}
