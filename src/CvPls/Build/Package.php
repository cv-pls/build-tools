<?php
/**
 * Interface for platform-specific package objects
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
 * Interface for platform-specific package objects
 *
 * @category [cv-pls]
 * @package  Build
 * @author   Chris Wright <info@daverandom.com>
 */
interface Package
{
    /**
     * Get the version to use for built package
     *
     * @return string Version to use for built package
     */
    public function getPackageVersion();

    /**
     * Get the path to output package binary
     *
     * @return string Path to output package binary
     */
    public function getOutputFilePath();

    /**
     * Determine whether an update manifest will be created
     *
     * @return bool Whether to create update manifest
     */
    public function willMakeUpdateManifest();

    /**
     * Get the path to output update manifest
     *
     * @return string Path to output update manifest
     */
    public function getUpdateManifestPath();

    /**
     * Validate the package
     */
    public function validate();

    /**
     * Build the package
     */
    public function build();
}
