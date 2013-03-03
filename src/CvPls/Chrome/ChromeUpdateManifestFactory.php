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
use \CvPls\Build\PackageFile;

/**
 * Factory which makes Chrome-specific update manifest objects
 *
 * @category [cv-pls]
 * @package  Chrome
 * @author   Chris Wright <info@daverandom.com>
 */
class ChromeUpdateManifestFactory implements UpdateManifestFactory
{
    /**
     * Create a new update manifest instance
     *
     * @param \CvPls\Build\DataSigner $dataSigner Data signer object
     * @param string                  $version    Version of package binary
     * @param string                  $packageUrl URL of package binary
     *
     * @return \CvPls\Chrome\ChromeUpdateManifest The created instance
     */
    public function create(DataSigner $dataSigner, $version, $packageUrl)
    {
        $idGenerator = new IdGenerator($dataSigner->getKeyPair());

        return new ChromeUpdateManifest($idGenerator, $version, $packageUrl);
    }
}
