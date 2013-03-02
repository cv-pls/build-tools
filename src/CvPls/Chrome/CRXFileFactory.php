<?php
/**
 * Factory which makes Chrome-specific package objects
 *
 * PHP version 5.4
 *
 * @category [cv-pls]
 * @package  Chrome
 * @author   Chris Wright <info@daverandom.com>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @version  1.0.0
 */

namespace CvPls\Chrome;

use \CvPls\Build\PackageFactory;
use \CvPls\Build\DataSigner;

/**
 * Factory which makes Chrome-specific package objects
 *
 * @category [cv-pls]
 * @package  Chrome
 * @author   Chris Wright <info@daverandom.com>
 */
class CRXFileFactory implements PackageFactory {
    /**
     * Create a new package builder instance
     *
     * @return \CvPls\Chrome\CRXFile The created instance
     */
    public function create(DataSigner $dataSigner)
    {
        $crxFile = new CRXFile;
        $crxFile->setDataSigner($dataSigner);

        return $crxFile;
    }
}
