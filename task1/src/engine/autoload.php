<?php

declare(strict_types=1);

spl_autoload_register(function ($className) {
    $baseDir = SRC_DIR; // Adjust the base directory as needed

    // Normalize class name
    $className = ltrim($className, '\\');
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);

    // Find all files that match the class name
    $files = findFilesWithClass($baseDir, $className);

    // Require all matching files
    foreach ($files as $file) {
        require_once $file;
    }
});

/**
 * Recursively search for files that match the class name.
 *
 * @param string $dir
 * @param string $className
 * @return array
 */
function findFilesWithClass(string $dir, string $className): array
{
    $directory = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
    $iterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::LEAVES_ONLY);
    $files = [];

    foreach ($iterator as $file) {
        if ($file->isFile() && preg_match('/\.(php|class\.php)$/', $file->getFilename())) {
            $filenameWithoutExtension = pathinfo($file->getFilename(), PATHINFO_FILENAME);

            // Check if the filename matches the class name
            if ($filenameWithoutExtension === basename($className)) {
                $files[] = $file->getRealPath();
            }
        }
    }

    return $files;
}
