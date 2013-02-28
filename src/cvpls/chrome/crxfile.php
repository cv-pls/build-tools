<?php
/**
 * CRX package builder for Chrome extensions
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

use \CvPls\Build\Package;
use \CvPls\Build\DataSigner;

/**
 * CRX package builder for Chrome extensions
 *
 * @category [cv-pls]
 * @package  Chrome
 * @author   Chris Wright <info@daverandom.com>
 */
class CRXFile extends Package
{
    /**
     * @var int The CRX version in use
     */
    private $crxVersion = 2;

    /**
     * Sign file data and return a CRX header in binary format
     *
     * @param string $data The file data
     *
     * @return string The CRX header
     */
    private function makeCrxHeader($data)
    {
        $publicKey = $this->dataSigner->getPublicKey(DataSigner::FORMAT_DER);
        $signature = $this->dataSigner->signString($data);

        $magicNumber = "Cr24";
        $crxVersion  = pack('V', $this->crxVersion);
        $keyLength   = pack('V', strlen($publicKey));
        $sigLength   = pack('V', strlen($signature));

        return $magicNumber . $crxVersion . $keyLength . $sigLength . $publicKey . $signature;
    }

    /**
     * Get CRX version in use
     *
     * @return int The CRX version in use
     */
    public function getCrxVersion()
    {
        return $this->crxVersion;
    }

    /**
     * Set CRX version in use
     *
     * @param int $version The new CRX version to use
     */
    public function setCrxVersion($version)
    {
        $this->crxVersion = (int) $version;
    }

    /**
     * Close the temporary file, sign and transfer the data to the output file
     *
     * @throws \RuntimeException When the output file cannot be written
     */
    public function close()
    {
        $data = $this->closeAndDestroyTempFile();
        $header = $this->makeCrxHeader($data);

        $this->writeOutputFile($header . $data);
    }
}
