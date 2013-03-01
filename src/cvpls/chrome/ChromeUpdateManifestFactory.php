<?php
/**
 * Factory which makes Chrome-specific update manifest objects
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

use \CvPls\Build\UpdateManifestFactory;
use \CvPls\Build\DataSigner;

/**
 * Factory which makes Chrome-specific update manifest objects
 *
 * @category [cv-pls]
 * @package  Chrome
 * @author   Chris Wright <info@daverandom.com>
 */
class ChromeUpdateManifestFactory implements UpdateManifestFactory {
    /**
     * Create a new update manifest instance
     *
     * @return \CvPls\Chrome\ChromeUpdateManifest The created instance
     */
    public function create(DataSigner $dataSigner)
    {
        return new ChromeUpdateManifest(new IdGenerator($dataSigner->getKeyPair()));
    }
}
