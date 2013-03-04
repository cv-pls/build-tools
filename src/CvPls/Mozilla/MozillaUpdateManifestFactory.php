<?php
/**
 * Factory which makes Mozilla-specific update manifest objects
 *
 * PHP version 5.4
 *
 * @category [cv-pls]
 * @package  Mozilla
 * @author   Chris Wright <info@daverandom.com>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @version  1.0.0
 */

namespace CvPls\Mozilla;

use \CvPls\Build\UpdateManifestFactory;
use \CvPls\Build\DataSigner;
use \CvPls\Build\Package;

/**
 * Factory which makes Mozilla-specific update manifest objects
 *
 * @category [cv-pls]
 * @package  Mozilla
 * @author   Chris Wright <info@daverandom.com>
 */
class MozillaUpdateManifestFactory implements UpdateManifestFactory
{
    /**
     * Create a new update manifest instance
     *
     * @param \CvPls\Build\DataSigner $dataSigner Data signer object
     * @param \CvPls\Build\Package    $package    Package object
     * @param string                  $packageUrl URL of package binary
     *
     * @return \CvPls\Chrome\MozillaUpdateManifest The created instance
     */
    public function create(DataSigner $dataSigner, Package $package, $packageUrl)
    {
    }
}
