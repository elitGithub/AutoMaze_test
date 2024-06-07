<?php

declare(strict_types = 1);

namespace Setup;

require_once ROOT_DIR . '/system/config.php';


/**
 *
 */
class Setup
{
    protected string $primaryConfigFile;
    private string   $rootDir;

    /**
     * Setup constructor.
     */
    public function __construct()
    {
        $this->primaryConfigFile = ROOT_DIR . '/system/config.php';
        $this->setRootDir(ROOT_DIR);
    }

    /**
     * Sets the root directory of the Expense Tracker instance.
     */
    public function setRootDir(string $rootDir): void
    {
        $this->rootDir = $rootDir;
    }

    /**
     * Checks basic folders and creates them if necessary.
     *
     * @param  string[]  $dirs
     *
     * @return string[]
     */
    public function checkDirs(array $dirs): array
    {
        $failedDirs = [];

        foreach ($dirs as $dir) {
            if (false === is_dir($this->rootDir . $dir)) {
                // If the folder does not exist, try to create it
                if (false === mkdir($this->rootDir . $dir)) {
                    // If the folder creation fails
                    $failedDirs[] = 'Folder [' . $this->rootDir . $dir . '] could not be created.';
                } elseif (false === chmod($this->rootDir . $dir, 0775)) {
                    $failedDirs[] = 'Folder [' . $this->rootDir . $dir . '] could not be given correct permissions (775).';
                }
                // The folder exists, check permissions
            } elseif (false === is_writable($this->rootDir . $dir)) {
                // If the folder exists but is not writeable
                $failedDirs[] = 'Folder [' . $this->rootDir . $dir . '] exists but is not writable.';
            }

            if (0 === count($failedDirs)) {
                // if no failed dirs exist
                copy(
                    $this->rootDir . '/install/index.html',
                    $this->rootDir . $dir . '/index.html'
                );
            }
        }

        return $failedDirs;
    }

    /**
     * @return array
     */
    public function checkNoncriticalSettings(): array
    {
        $potentialIssues = [];
        if (!extension_loaded('gd')) {
            $potentialIssues[] = "You don't have GD support enabled in your PHP installation. Please enable GD support in your php.ini file otherwise you can't use Captchas for spam protection.";
        }
        if (!function_exists('imagettftext')) {
            $potentialIssues[] = "You don't have Freetype support enabled in the GD extension of your PHP installation. Please enable Freetype support in GD extension otherwise the Captchas for spam protection will be quite easy to break. ";
        }
        if (!extension_loaded('curl') || !extension_loaded('openssl')) {
            $potentialIssues[] = "You don't have cURL and/or OpenSSL support enabled in your PHP installation. Please enable cURL and/or OpenSSL support in your php.ini file otherwise you can't use the Twitter support and/or Elasticsearch.";
        }
        if (!extension_loaded('fileinfo')) {
            $potentialIssues[] = "You don't have Fileinfo support enabled in your PHP installation. Please enable Fileinfo support in your php.ini file otherwise you can't use our backup/restore functionality.";
        }
        if (!extension_loaded('sodium')) {
            $potentialIssues[] = "You don't have Sodium support enabled in your PHP installation. Please enable Sodium support in your php.ini file otherwise you can't use our backup/restore functionality";
        }
        return $potentialIssues;
    }
}
