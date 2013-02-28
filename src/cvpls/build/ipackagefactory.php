<?php
/**
 * Interface for factories which make browser-specific builder objects
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
 * Interface for factories which make browser-specific builder objects
 *
 * @category [cv-pls]
 * @package  Build
 * @author   Chris Wright <info@daverandom.com>
 */
interface BrowserFileFactory {
    /**
     * Create a new package builder instance
     *
     * @return \CvPls\Build\Package The created instance
     */
    public function createPackage();

    /**
     * Create a new update manifest builder instance
     *
     * @return \CvPls\Build\UpdateManifest The created instance
     */
    public function createUpdateManifest();
}
