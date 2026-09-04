<?php

namespace Icinga\Module\Perfdatagraphs\Common;

use Icinga\Web\FileCache;

use FilesystemIterator;

/**
* Perfdata is a small wrapper around the FileCache
* to provide additional features we might require.
*/
class PerfdataCache extends FileCache
{
    /**
    * clearAll removes all items from the cache
    */
    public function clearAll(): void
    {
        // Probably not gonna happen but why not
        if (!is_dir($this->basedir)) {
            return;
        }

        $iterator = new FilesystemIterator($this->basedir);
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $this->clear($file->getFilename());
        }
    }

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
