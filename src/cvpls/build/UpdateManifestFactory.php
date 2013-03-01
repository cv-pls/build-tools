<?php
/**
 * Interface for factories which make browser-specific update manifest objects
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
 * Interface for factories which make browser-specific update manifest objects
 *
 * @category [cv-pls]
 * @package  Build
 * @author   Chris Wright <info@daverandom.com>
 */
interface UpdateManifestFactory {
    /**
     * Create a new update manifest instance
     *
     * @return \CvPls\Build\UpdateManifest The created instance
     */
    public function create(DataSigner $dataSigner, Package $package, $version, $url);
}
