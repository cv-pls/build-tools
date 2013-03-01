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

use \CvPls\Build\DataSigner;

/**
 * Chrome extension ID generator class
 *
 * @category [cv-pls]
 * @package  Chrome
 * @author   Chris Wright <info@daverandom.com>
 */
class ChromeUpdateManifest
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
     * @var string URL of CRX package
     */
    private $crxUrl;

    /**
     * @var string Version of CRX package
     */
    private $version;

    /**
     * @var \DOMDocument Manifest XML document
     */
    protected $document;

    /**
     * Constructor
     *
     * @param \CvPls\Chrome\IdGenerator $idGenerator Extension ID generator
     * @param string                    $crxUrl      URL of CRX package
     * @param string                    $version     Version of CRX package
     */
    public function __construct(IdGenerator $idGenerator = NULL, $crxUrl = NULL, $version = NULL)
    {
        if ($idGenerator !== NULL) {
            $this->setIdGenerator($idGenerator);
        }
        $this->setCrxUrl($crxUrl);
        $this->setVersion($version);
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
     * Get the URL of CRX package
     *
     * @return string URL of CRX package
     */
    public function getCrxUrl()
    {
        return $this->crxUrl;
    }

    /**
     * Set the URL of CRX package
     *
     * @param string $crxUrl URL of CRX package
     */
    public function setCrxUrl($crxUrl)
    {
        $this->crxUrl = $crxUrl;
    }

    /**
     * Get the version of CRX package
     *
     * @return string Version of CRX package
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set the version of CRX package
     *
     * @param string $version Version of CRX package
     */
    public function setVersion($version)
    {
        $this->version = $version;
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
        $updateCheckEl->setAttribute('codebase', $this->crxUrl);
        $updateCheckEl->setAttribute('version', $this->version);
        $appEl->appendChild($updateCheckEl);
    }

    /**
     * Save the update manifest document to file or string
     *
     * @param string $path Path to output file or omit to return data
     *
     * @return bool|string If writing to file then true on success, otherwise the file data as a string
     */
    public function save($path = NULL)
    {
        if (isset($path)) {
            return $this->document->save($path);
        } else {
            return $this->document->saveXML();
        }
    }
}
