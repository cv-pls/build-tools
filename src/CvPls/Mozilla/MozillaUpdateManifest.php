<?php
/**
 * Update manifest builder for Mozilla
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

use \CvPls\Build\UpdateManifest,
    \CvPls\Build\DataSigner,
    \CvPls\Build\Package;

/**
 * Update manifest builder for Mozilla
 *
 * @category [cv-pls]
 * @package  Mozilla
 * @author   Chris Wright <info@daverandom.com>
 */
class MozillaUpdateManifest extends UpdateManifest
{
    /**
     * @var string The W3 XML namespace URI
     */
    private $w3nsUrl = 'http://www.w3.org/2000/xmlns/';

    /**
     * @var string The W3 RDF namespace URI
     */
    private $rdfUrl = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';

    /**
     * @var string The Mozilla RDF namespace URI
     */
    private $emUrl = 'http://www.mozilla.org/2004/em-rdf#';

    /**
     * @var string GUID of the Firefox application
     */
    private $firefoxGUID = '{ec8030f7-c20a-464f-9b0e-13a3a9e97384}';

    /**
     * @var string GUID of the cv-pls extension
     */
    private $extensionGUID = 'cv-pls@stackoverflow.com';

    /**
     * @var \CvPls\Build\DataSigner Data signer object
     */
    private $dataSigner;

    /**
     * @var \DOMDocument Update manifest document
     */
    private $document;

    /**
     * @var \DOMElement Root element of update manifest document
     */
    private $rootEl;

    /**
     * @var \DOMElement Body container element of update manifest document
     */
    private $containerEl;

    /**
     * @var \DOMElement Sequence container element of update manifest document
     */
    private $seqEl;

    /**
     * Constructor
     *
     * @param \CvPls\Build\DataSigner $dataSigner Data signer object
     * @param \CvPls\Build\Package    $package    Package object
     * @param string                  $url        URL of package binary
     */
    public function __construct(DataSigner $dataSigner, Package $package = null, $packageUrl = null)
    {
        if (isset($dataSigner)) {
            $this->setDataSigner($dataSigner);
        }
        if (isset($package)) {
            $this->setPackage($package);
        }

        $this->setPackageUrl($packageUrl);
    }

    /**
     * Get the length of a data string for use in DER formatted wrapper
     *
     * @param string $data The data
     *
     * @return string The encoded length
     */
    private function getDERLength($data)
    {
        $length = strlen($data);
        if ($length < 128) {
            return chr($length);
        } else {
            // Lazy option that limits the data size to 4.3GB - OK for the purposes of this class
            // Theoretically the DER standard allows for a length up to 2^1008
            // TODO: fix this
            $length = ltrim(pack('N', $length), "\x00");
            return chr(strlen($length) | 0x80).$length;
        }
    }

    /**
     * Get the hash of a file on disk in a format suitable for a mozilla update manifest
     *
     * @param string $file Path to file
     *
     * @return string The file hash
     */
    private function getFileHash($file)
    {
        return 'sha512:' . hash_file('sha512', $file);
    }

    /**
     * Sign a string of data in a way Mozilla likes.
     *
     * For the good of your health, don't ask or try to understand.
     *
     * @param string $signatureTarget Data to sign
     *
     * @return string The signature
     */
    private function generateSignature($signatureTarget)
    {
        $signature = $this->dataSigner->signString($signatureTarget, 'sha512');

        // There are some rather interesting standards violations that Mozilla engage in for this.
        // Here is the first of them. I have no idea where this random null byte comes from.
        $signature = "\x00".$signature;

        // Encode as a DER BIT STRING field
        $derSignature = "\x03".$this->getDERLength($signature).$signature;

        // Here is another one
        // The standard states that there should be a NULL short on the end of the SEQUENCE (\x05\x00)
        $derAlgoId = "\x30\x0b\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x0d";

        // Encode as SEQUENCE
        $derData = "\x30".$this->getDERLength($derAlgoId.$derSignature).$derAlgoId.$derSignature;

        return base64_encode($derData);
    }

    /**
     * Initialise update manifest document
     */
    private function initialiseDocument()
    {
        $this->document = new \DOMDocument('1.0', 'utf-8');
        $this->document->formatOutput = true;

        $this->rootEl = $this->document->createElementNS($this->rdfUrl, 'RDF:RDF');
        $this->rootEl->setAttributeNS($this->w3nsUrl, 'xmlns:em', $this->emUrl);
        $this->document->appendChild($this->rootEl);
    }

    /**
     * Create main container element
     */
    private function createRDFContainer()
    {
        $this->containerEl = $this->document->createElementNS($this->rdfUrl, 'RDF:Description');
        $this->containerEl->setAttribute('about', 'urn:mozilla:extension:'.$this->extensionGUID);
        $this->rootEl->appendChild($this->containerEl);

        $updatesEl = $this->document->createElementNS($this->emUrl, 'em:updates');
        $this->containerEl->appendChild($updatesEl);

        $this->seqEl = $this->document->createElementNS($this->rdfUrl, 'RDF:Seq');
        $updatesEl->appendChild($this->seqEl);
    }

    private function createRDFVersion()
    {
        $liEl = $this->document->createElementNS($this->rdfUrl, 'RDF:li');
        $this->seqEl->appendChild($liEl);
        $itemEl = $this->document->createElementNS($this->rdfUrl, 'RDF:Description');
        $itemEl->setAttribute('about', 'urn:mozilla:extension:'.$this->extensionGUID.':'.$this->package->getPackageVersion());
        $liEl->appendChild($itemEl);

        $targetAppEl = $this->document->createElementNS($this->emUrl, 'em:targetApplication');
        $itemEl->appendChild($targetAppEl);
        $appContainerEl = $this->document->createElementNS($this->rdfUrl, 'RDF:Description');
        $targetAppEl->appendChild($appContainerEl);

        // Keys MUST be alphabetical order!
        $info = [
            'id' => $this->firefoxGUID,
            'maxVersion' => '99.*',
            'minVersion' => '10.0',
            'updateHash' => $this->getFileHash($this->package->getOutputFilePath()),
            'updateLink' => $this->packageUrl
        ];
        foreach ($info as $tagName => $data) {
            $appContainerEl->appendChild($this->document->createElementNS($this->emUrl, "em:$tagName", htmlspecialchars($data)));
        }

        $versionEl = $this->document->createElementNS($this->emUrl, 'em:version', $this->package->getPackageVersion());
        $itemEl->appendChild($versionEl);
    }

    /**
     * Sign the update manifest document
     */
    private function signManifest()
    {
        $signatureTarget = $this->document->saveXML($this->containerEl)."\n";
        $signature = $this->generateSignature($signatureTarget);
        $signatureEl = $this->document->createElementNS($this->emUrl, 'em:signature', $signature);
        $this->containerEl->appendChild($signatureEl);
    }

    /**
     * Get the data signer object
     *
     * @return \CvPls\Build\DataSigner Data signer object
     */
    public function getDataSigner()
    {
        return $this->package;
    }

    /**
     * Set the data signer object
     *
     * @param \CvPls\Build\DataSigner $dataSigner Data signer object
     */
    public function setDataSigner(DataSigner $dataSigner)
    {
        $this->dataSigner = $dataSigner;
    }

    /**
     * Generate the update manifest document
     */
    public function generate()
    {
        $this->initialiseDocument();
        $this->createRDFContainer();
        $this->createRDFVersion();
        $this->signManifest();
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
        $result = $this->document->saveXML();

        if (isset($path)) {
            return file_put_contents($path, $result);
        } else {
            return $result;
        }
    }
}
