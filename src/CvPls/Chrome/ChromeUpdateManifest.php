<?php
/**
 * Update manifest builder for Chrome
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

use \CvPls\Build\UpdateManifest,
    \CvPls\Build\Package;

/**
 * Update manifest builder for Chrome
 *
 * @category [cv-pls]
 * @package  Chrome
 * @author   Chris Wright <info@daverandom.com>
 */
class ChromeUpdateManifest extends UpdateManifest
{
    /**
     * @var string Namespace URL for manifest XML
     */
    private $nsUrl = 'http://www.google.com/update2/response';

    /**
     * @var \CvPls\Chrome\IdGenerator Extension ID generator
     */
    private $idGenerator;

    /**
     * @var \DOMDocument Manifest XML document
     */
    private $document;

    /**
     * Constructor
     *
     * @param \CvPls\Chrome\IdGenerator $idGenerator Extension ID generator
     * @param \CvPls\Build\Package      $package     Package object
     * @param string                    $url         URL of package binary
     */
    public function __construct(IdGenerator $idGenerator = null, Package $package = null, $packageUrl = null)
    {
        if (isset($idGenerator)) {
            $this->setIdGenerator($idGenerator);
        }
        if (isset($package)) {
            $this->setPackage($package);
        }
        $this->setPackageUrl($packageUrl);
    }

    /**
     * Get the extension ID generator object
     *
     * @return \CvPls\Chrome\IdGenerator Extension ID generator
     */
    public function getIdGenerator()
    {
        return $this->idGenerator;
    }

    /**
     * Set the extension ID generator object
     *
     * @param \CvPls\Chrome\IdGenerator $idGenerator Extension ID generator
     */
    public function setIdGenerator(IdGenerator $idGenerator)
    {
        $this->idGenerator = $idGenerator;
    }

    /**
     * Generate the update manifest document
     */
    public function generate()
    {
        $this->document = new \DOMDocument('1.0', 'utf-8');
        $this->document->formatOutput = true;

        $rootEl = $this->document->createElementNS($this->nsUrl, 'gupdate');
        $rootEl->setAttribute('protocol', '2.0');
        $this->document->appendChild($rootEl);

        $appId = $this->idGenerator->getAppId();
        $appEl = $this->document->createElementNS($this->nsUrl, 'app');
        $appEl->setAttribute('appid', $appId);
        $rootEl->appendChild($appEl);

        $updateCheckEl = $this->document->createElementNS($this->nsUrl, 'updatecheck');
        $updateCheckEl->setAttribute('codebase', $this->packageUrl);
        $updateCheckEl->setAttribute('version', $this->package->getPackageVersion());
        $appEl->appendChild($updateCheckEl);
    }

    /**
     * Save the update manifest document to file or string
     *
     * @param string $path Path to output file or omit to return data
     *
     * @return bool|string If writing to file then true on success, otherwise the file data as a string
     */
    public function save($path = null)
    {
        if (isset($path)) {
            return $this->document->save($path);
        } else {
            return $this->document->saveXML();
        }
    }
}
