<?php
/**
 * Interface for factories which make browser-specific package file objects
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
 * Interface for factories which make browser-specific package file objects
 *
 * @category [cv-pls]
 * @package  Build
 * @author   Chris Wright <info@daverandom.com>
 */
interface PackageFileFactory {
    /**
     * Create a new package instance
     *
     * @return \CvPls\Build\PackageFile The created instance
     */
    public function create(DataSigner $dataSigner);
}
