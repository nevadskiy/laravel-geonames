<?php

namespace Nevadskiy\Geonames\Support\Exporter;

use RuntimeException;

class ArrayExporter
{
    /**
     * Export the given array into a PHP file.
     */
    public function export(array $array, string $path): void
    {
        $this->ensurePathHasPhpExtension($path);
        $this->write($path, $array);
    }

    /**
     * Write the given array into a file with the given path.
     */
    protected function write(string $path, array $array): void
    {
        file_put_contents($path, '<?php return '.var_export($array, true).";\n");
    }

    /**
     * Ensure that the given path has PHP extension.
     */
    protected function ensurePathHasPhpExtension(string $path): void
    {
        if (substr($path, -4) !== '.php') {
            throw new RuntimeException("File {$path} must have PHP extension.");
        }
    }
}
