<?php

namespace Icinga\Module\Perfdatagraphs\Common;

use Icinga\Web\FileCache;

/**
* Perfdata is a small wrapper around the FileCache
* to provide additional features we might require.
*/
class PerfdataCache extends FileCache
{
    /**
    * clear removes a single item from the cache by its name
    */
    public function clear(string $name): bool
    {
        if ($this->has($name)) {
            return unlink($this->filename($name));
        }

        return false;
    }
}
