<?php
/**
 * XPI package builder
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

use \CvPls\Build\Arguments,
    \CvPls\Build\KeyPairFactory,
    \CvPls\Build\DataSignerFactory,
    \CvPls\Build\Package,
    \CvPls\Mozilla\XPIFileFactory,
    \CvPls\Mozilla\MozillaUpdateManifestFactory,
    \CvPls\Build\Loggable;

/**
 * XPI package builder
 *
 * @category [cv-pls]
 * @package  Mozilla
 * @author   Chris Wright <info@daverandom.com>
 */
class XPIPackage implements Package
{
    /**
     * @var \CvPls\Build\Arguments Package arguments
     */
    private $arguments;

    /**
     * @var \CvPls\Build\KeyPairFactory Factory which makes key pair objects
     */
    private $keyPairFactory;

    /**
     * @var \CvPls\Build\DataSignerFactory Factory which makes data signer objects
     */
    private $dataSignerFactory;

    /**
     * @var \CvPls\Build\XPIFileFactory Factory which makes XPI file objects
     */
    private $xpiFileFactory;

    /**
     * @var \CvPls\Build\MozillaUpdateManifestFactory Factory which makes update manifest objects
     */
    private $updateManifestFactory;

    /**
     * @var \CvPls\Build\Loggable Logging object
     */
    private $logger;

    /**
     * @var \CvPls\Build\KeyPair Key pair object
     */
    private $keyPair;

    /**
     * @var \DOMDocument Install RDF document
     */
    private $installRdf;

    /**
     * @var \DOMXPath Install RDF xpath object
     */
    private $installRdfXpath;

    /**
     * @var string Version to use for built package
     */
    private $packageVersion;

    /**
     * @var string Path to output package binary
     */
    private $outputFilePath;

    /**
     * @var bool Whether to create update manifest
     */
    private $makeUpdateManifest = false;

    /**
     * @var string Path to output update manifest
     */
    private $updateManifestPath;

    /**
     * Constructor
     *
     * @param \CvPls\Build\Arguments                      $arguments             Package arguments
     * @param \CvPls\Build\KeyPairFactory                 $keyPairFactory        Factory which makes key pair objects
     * @param \CvPls\Build\DataSignerFactory              $dataSignerFactory     Factory which makes data signer objects
     * @param \CvPls\Mozilla\XPIFileFactory               $xpiFileFactory        Factory which makes XPI file objects
     * @param \CvPls\Mozilla\MozillaUpdateManifestFactory $updateManifestFactory Factory which makes update manifest objects
     * @param \CvPls\Build\Loggable                       $logger                Logging object
     */
    public function __construct(
        Arguments $arguments,
        KeyPairFactory $keyPairFactory,
        DataSignerFactory $dataSignerFactory,
        XPIFileFactory $xpiFileFactory,
        MozillaUpdateManifestFactory $updateManifestFactory,
        Loggable $logger = null
    ) {
        $this->arguments = $arguments;
        $this->keyPairFactory = $keyPairFactory;
        $this->dataSignerFactory = $dataSignerFactory;
        $this->xpiFileFactory = $xpiFileFactory;
        $this->updateManifestFactory = $updateManifestFactory;
        $this->logger = $logger;
    }

    /**
     * Log a message
     *
     * @param string $message Message to log
     */
    private function log($message)
    {
        if (isset($this->logger)) {
            $this->logger->log($message);
        }
    }

    /**
     * Load manifest data from file
     */
    private function loadInstallRdf()
    {
        $this->log('Loading install.rdf');

        $manifestPath = $this->arguments->getBaseDir() . '/install.rdf';
        if (!is_file($manifestPath) || !is_readable($manifestPath)) {
            throw new \RuntimeException('install.rdf does not exist or is not readable');
        }

        $raw = file_get_contents($manifestPath);
        if (!$raw) {
            throw new \RuntimeException('Reading install.rdf failed');
        }

        $installRdf = new \DOMDocument;
        if (!@$installRdf->loadXML($raw)) {
            throw new \RuntimeException('install.rdf does not contain valid XML');
        }

        $this->installRdf = $installRdf;
        $this->installRdfXpath = new \DOMXpath($installRdf);
        $this->installRdfXpath->registerNamespace('em', 'http://www.mozilla.org/2004/em-rdf#');
    }

    /**
     * Resolve package version to use
     */
    private function resolvePackageVersion()
    {
        $versionNode = $this->installRdfXpath->query('//em:version')->item(0)->firstChild;
        if ($version = $this->arguments->getVersion()) {
            $this->packageVersion = $versionNode->data = $version;
        } else {
            $this->packageVersion = $versionNode->data;
            $this->log('Package version: ' . $this->packageVersion);
        }
    }

    /**
     * Resolve output file path
     */
    private function resolveOutputFilePath()
    {
        if ($path = $this->arguments->getOutFile()) {
            $this->outputFilePath = $path;
        } else {
            $this->outputFilePath = getcwd() . DIRECTORY_SEPARATOR . "cv-pls_{$this->packageVersion}.xpi";
            $this->log('Output file path: ' . $this->outputFilePath);
        }
    }

    /**
     * Resolve update manifest path
     */
    private function resolveUpdateManifestPath()
    {
        $path = $this->arguments->getManifestFile();

        if ($path !== false) {
            $this->makeUpdateManifest = true;

            if ($path = $this->arguments->getManifestFile()) {
                $this->updateManifestPath = $path;
            } else {
                $this->updateManifestPath = getcwd() . DIRECTORY_SEPARATOR . "update_{$this->packageVersion}.rdf";
                $this->log('Update manifest file path: ' . $this->outputFilePath);
            }
        }
    }

    /**
     * Load the private key
     */
    private function createKeyPair()
    {
        $this->log('Loading cryptographic keys');
        $this->keyPair = $this->keyPairFactory->create($this->arguments->getKeyFile());
    }

    /**
     * Build the XPI file
     */
    private function createXpiFile($dataSigner)
    {
        $this->log('Building package binary');
        $xpiFile = $this->xpiFileFactory->create($dataSigner);
        $xpiFile->open($this->outputFilePath);

        $files = glob($this->arguments->getBaseDir() . '/*');
        foreach ($files as $file) {
            if ($file !== $this->arguments->getBaseDir() . '/install.rdf') {
                $this->log('Adding file: ' . $file);
                if (is_file($file)) {
                    $xpiFile->addFile($file, basename($file));
                } else {
                    $xpiFile->addDir($file);
                }
            }
        }

        $this->log('Adding extension manifest');
        $xpiFile->addFromString('install.rdf', $this->installRdf->saveXML());

        $this->log('Compressing package');
        $xpiFile->close();

        $this->log('Binary built successfully');
    }

    /**
     * Build the XPI file
     */
    private function createUpdateManifest($dataSigner)
    {
        if ($this->makeUpdateManifest) {
            $manifest = $this->updateManifestFactory->create($dataSigner, $this, $this->arguments->getURL());

            $manifest->generate();

            $manifest->save($this->updateManifestPath);
        }
    }

    /**
     * Get the version to use for built package
     *
     * @return string Version to use for built package
     */
    public function getPackageVersion()
    {
        return $this->packageVersion;
    }

    /**
     * Get the path to output package binary
     *
     * @return string Path to output package binary
     */
    public function getOutputFilePath()
    {
        return $this->outputFilePath;
    }

    /**
     * Determine whether an update manifest will be created
     *
     * @return bool Whether to create update manifest
     */
    public function willMakeUpdateManifest()
    {
        return $this->makeUpdateManifest;
    }

    /**
     * Get the path to output update manifest
     *
     * @return string Path to output update manifest
     */
    public function getUpdateManifestPath()
    {
        return $this->updateManifestPath;
    }

    /**
     * Validate the package
     */
    public function validate()
    {
        $this->loadInstallRdf();

        $this->resolvePackageVersion();
        $this->resolveOutputFilePath();
        $this->resolveUpdateManifestPath();

        $this->createKeyPair();
    }

    /**
     * Build the package
     */
    public function build()
    {
        $this->log('Building Mozilla extension');

        $dataSigner = $this->dataSignerFactory->create($this->keyPair);

        $this->createXpiFile($dataSigner);
        $this->createUpdateManifest($dataSigner);
    }
}
