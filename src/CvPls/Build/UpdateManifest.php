<?php
/**
 * Abstract update manifest builder
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
 * Abstract update manifest builder
 *
 * @category [cv-pls]
 * @package  Build
 * @author   Chris Wright <info@daverandom.com>
 */
abstract class UpdateManifest
{
    /**
     * @var string URL of package binary
     */
    protected $packageUrl;

    /**
     * @var string Version of package binary
     */
    protected $version;

    /**
     * Get the URL of package binary
     *
     * @return string URL of package binary
     */
    public function getPackageUrl()
    {
        return $this->packageUrl;
    }

    /**
     * Set the URL of package binary
     *
     * @param string $packageUrl URL of package binary
     */
    public function setPackageUrl($packageUrl)
    {
        $this->packageUrl = $packageUrl;
    }

    /**
     * Get the version of package binary
     *
     * @return string Version of package binary
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set the version of package binary
     *
     * @param string $version Version of package binary
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * Generate the update manifest document
     */
    abstract public function generate();

    /**
     * Save the update manifest document to file or string
     *
     * @param string $path Path to output file or omit to return data
     *
     * @return bool|string If writing to file then true on success, otherwise the file data as a string
     */
    abstract public function save($path = null);
}
