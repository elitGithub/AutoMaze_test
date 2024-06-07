<?php

declare(strict_types = 1);

spl_autoload_register(function ($className) {
    $baseDir = SRC_DIR; // Adjust the base directory as needed

    // Normalize class name for namespace and directory separator
    $className = ltrim($className, '\\');

    // Generate the classes map using RecursiveDirectoryIterator
    $classesMap = scanDirectoryForClassesUsingIterator($baseDir);


    if ($path = getClassPathUsingIterator($className, $classesMap)) {
        require_once $path;
    }
});

/**
 * @param $dir
 *
 * @return array
 */
function scanDirectoryForClassesUsingIterator($dir): array
{
    $directory = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
    $iterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::LEAVES_ONLY);
    $classesMap = [];

    foreach ($iterator as $file) {
        if ($file->isFile() && preg_match('/\.(php|class\.php)$/', $file->getFilename())) {
            $path = $file->getRealPath();
            $className = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            if (isset($classesMap[$className])) {
                $classPath = explode(DIRECTORY_SEPARATOR, $path);
                $lastKey = array_key_last($classPath);
                $className = $classPath[$lastKey];
                if ($lastKey > 0) {
                    $keyBeforeLast = array_key_last($classPath) - 1;
                    $className = join(DIRECTORY_SEPARATOR, [$classPath[$keyBeforeLast], $className]);
                    $className = str_replace('.php', '', $className);
                }
            }
            $classesMap[$className] = $path;
        }
    }

    return $classesMap;
}

/**
 * @param $className
 * @param $classesMap
 *
 * @return mixed|null
 */
function getClassPathUsingIterator($className, $classesMap)
{
    $simpleClassName = null;
    $classPath = explode('\\', $className);

    $lastKey = array_key_last($classPath);
    $nameSpacedClassName = $classPath[$lastKey];
    if ($lastKey > 0) {
        $keyBeforeLast = array_key_last($classPath) - 1;
        $simpleClassName = join(DIRECTORY_SEPARATOR, [$classPath[$keyBeforeLast], $nameSpacedClassName]);
    }

    if (isset($classesMap[$simpleClassName])) {
        return $classesMap[$simpleClassName];
    }
    // Split class name into parts to handle namespace if needed
    $classNameParts = explode('\\', $className);
    $simpleClassName = end($classNameParts);


    if (isset($classesMap[$simpleClassName])) {
        return $classesMap[$simpleClassName];
    }
    return null;
}
