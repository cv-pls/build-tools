<?php
/**
 * Class that represents a parsed and validated argument structure
 *
 * PHP version 5.4
 *
 * @category [cv-pls]
 * @package  Build
 * @author   Chris Wright <info@daverandom.com>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @version  1.0.0
 */

namespace CvPls\Build;

/**
 * Class that represents a parsed and validated argument structure
 *
 * @category [cv-pls]
 * @package  Build
 * @author   Chris Wright <info@daverandom.com>
 */
class Arguments
{
    /**
     * @var string Target platform identifier
     */
    private $platform;

    /**
     * @var string Path to private key file
     */
    private $keyFile;

    /**
     * @var string Extension source code base directory
     */
    private $baseDir;

    /**
     * @var string Package version
     */
    private $version;

    /**
     * @var string Path to package output file
     */
    private $outFile;

    /**
     * @var string Path to update manifest file
     */
    private $manifestFile;

    /**
     * @var string Full URL of package file
     */
    private $url;

    /**
     * Create an arguments object
     *
     * @param string $platform     Target platform identifier
     * @param string $keyFile      Path to private key file
     * @param string $baseDir      Extension source code base directory
     * @param string $version      Package version
     * @param string $outFile      Path to package output file
     * @param string $manifestFile Path to update manifest file
     * @param string $url          Full URL of package file
     */
    public function __construct($platform, $keyFile, $baseDir, $version, $outFile, $manifestFile, $url)
    {
        $this->platform = $platform;
        $this->keyFile = $keyFile;
        $this->baseDir = $baseDir;
        $this->version = $version;
        $this->outFile = $outFile;
        $this->manifestFile = $manifestFile;
        $this->url = $url;
    }

    /**
     * Get the target platform identifier
     *
     * @return string Target platform identifier
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * Get the path to private key file
     *
     * @return string Path to private key file
     */
    public function getKeyFile()
    {
        return $this->keyFile;
    }

    /**
     * Get the extension source code base directory
     *
     * @return string Extension source code base directory
     */
    public function getBaseDir()
    {
        return $this->baseDir;
    }

    /**
     * Get the package version
     *
     * @return string Package version
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Get the path to package output file
     *
     * @return string Path to package output file
     */
    public function getOutFile()
    {
        return $this->outFile;
    }

    /**
     * Get the path to update manifest file
     *
     * @return string Path to update manifest file
     */
    public function getManifestFile()
    {
        return $this->manifestFile;
    }

    /**
     * Get the full URL of package file
     *
     * @return string Full URL of package file
     */
    public function getURL()
    {
        return $this->url;
    }
}
