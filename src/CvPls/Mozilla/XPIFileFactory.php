<?php
/**
 * Factory which makes Mozilla-specific package file objects
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

use \CvPls\Build\PackageFileFactory;
use \CvPls\Build\DataSigner;

/**
 * Factory which makes Mozilla-specific package file objects
 *
 * @category [cv-pls]
 * @package  Mozilla
 * @author   Chris Wright <info@daverandom.com>
 */
class XPIFileFactory implements PackageFileFactory
{
    /**
     * Create a new package file instance
     *
     * @return \CvPls\Mozilla\XPIFile The created instance
     */
    public function create(DataSigner $dataSigner)
    {
        return new XPIFile;
    }
}
