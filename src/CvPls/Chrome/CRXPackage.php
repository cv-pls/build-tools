<?php
/**
 * CRX package builder
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

use \CvPls\Build\Arguments,
    \CvPls\Build\KeyPairFactory,
    \CvPls\Build\DataSignerFactory,
    \CvPls\Chrome\CRXFileFactory,
    \CvPls\Chrome\ChromeUpdateManifestFactory,
    \CvPls\Build\Loggable;

/**
 * CRX package builder
 *
 * @category [cv-pls]
 * @package  Chrome
 * @author   Chris Wright <info@daverandom.com>
 */
class CRXPackage
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
     * @var \CvPls\Build\CRXFileFactory Factory which makes CRX file objects
     */
    private $crxFileFactory;

    /**
     * @var \CvPls\Build\ChromeUpdateManifestFactory Factory which makes update manifest objects
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
     * @var \stdClass Package manifest data
     */
    private $manifest;

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
     * @param \CvPls\Build\Arguments                    $arguments             Package arguments
     * @param \CvPls\Build\KeyPairFactory               $keyPairFactory        Factory which makes key pair objects
     * @param \CvPls\Build\DataSignerFactory            $dataSignerFactory     Factory which makes data signer objects
     * @param \CvPls\Chrome\CRXFileFactory              $crxFileFactory        Factory which makes CRX file objects
     * @param \CvPls\Chrome\ChromeUpdateManifestFactory $updateManifestFactory Factory which makes update manifest objects
     * @param \CvPls\Build\Loggable                     $logger                Logging object
     */
    public function __construct(
        Arguments $arguments,
        KeyPairFactory $keyPairFactory,
        DataSignerFactory $dataSignerFactory,
        CRXFileFactory $crxFileFactory,
        ChromeUpdateManifestFactory $updateManifestFactory,
        Loggable $logger = null
    ) {
        $this->arguments = $arguments;
        $this->keyPairFactory = $keyPairFactory;
        $this->dataSignerFactory = $dataSignerFactory;
        $this->crxFileFactory = $crxFileFactory;
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
    private function loadManifestFile()
    {
        $this->log('Loading manifest file');

        $manifestPath = $this->arguments->getBaseDir() . '/manifest.json';
        if (!is_file($manifestPath) || !is_readable($manifestPath)) {
            throw new \RuntimeException('Manifest file does not exist or is not readable');
        }

        $raw = file_get_contents($manifestPath);
        if (!$raw) {
            throw new \RuntimeException('Reading manifest file failed');
        }

        if (!$manifest = json_decode($raw)) {
            throw new \RuntimeException('Manifest file does not contain valid JSON');
        }

        $this->manifest = $manifest;
    }

    /**
     * Resolve package version to use
     */
    private function resolvePackageVersion()
    {
        if ($version = $this->arguments->getVersion()) {
            $this->packageVersion = $this->manifest->version = $version;
        } else {
            $this->packageVersion = $this->manifest->version;
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
            $this->outputFilePath = getcwd() . DIRECTORY_SEPARATOR . "cv-pls_{$this->packageVersion}.crx";
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
                $this->updateManifestPath = getcwd() . DIRECTORY_SEPARATOR . "update_{$this->packageVersion}.xml";
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
     * Validate the package
     */
    public function validate()
    {
        $this->loadManifestFile();

        $this->resolvePackageVersion();
        $this->resolveOutputFilePath();
        $this->resolveUpdateManifestPath();

        $this->createKeyPair();
    }

    /**
     * Build the CRX file
     */
    private function createCrxFile($dataSigner)
    {
        $crxFile = $this->crxFileFactory->create($dataSigner);
        $crxFile->open($this->outputFilePath);

        $files = glob($this->arguments->getBaseDir() . '/*');
        foreach ($files as $file) {
            if ($file !== $this->arguments->getBaseDir() . '/manifest.json') {
                $this->log('Adding file: ' . $file);
                if (is_file($file)) {
                    $crxFile->addFile($file, basename($file));
                } else {
                    $crxFile->addDir($file);
                }
            }
        }

        $crxFile->addFromString('manifest.json', json_encode($this->manifest));

        $crxFile->close();
    }

    /**
     * Build the CRX file
     */
    private function createUpdateManifest($dataSigner)
    {
        if ($this->makeUpdateManifest) {
            $manifest = $this->updateManifestFactory->create($dataSigner, $this->packageVersion, $this->arguments->getURL());

            $manifest->generate();

            $manifest->save($this->updateManifestPath);
        }
    }

    /**
     * Build the package
     */
    public function build()
    {
        $this->log('Building Chrome extension');

        $dataSigner = $this->dataSignerFactory->create($this->keyPair);

        $this->createCrxFile($dataSigner);
        $this->createUpdateManifest($dataSigner);
    }
}
