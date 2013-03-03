<?php
/**
 * Signs data using cryptographic keys
 *
 * PHP version 5.4
 *
 * @category [cv-pls]
 * @package  Build
 * @author   Chris Wright <info@daverandom.com>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @version  1.0.0
 */

namespace CvPls\Build;

/**
 * CRX package builder for Chrome extensions
 *
 * @category [cv-pls]
 * @package  Build
 * @author   Chris Wright <info@daverandom.com>
 */
class DataSigner
{
    /**
     * @var \CvPls\Build\KeyPair The cryptographic key pair
     */
    private $keyPair;

    /**
     * Constructor
     *
     * @param \CvPls\Build\KeyPair $keyPair The cryptographic key pair
     */
    public function __construct(KeyPair $keyPair = null)
    {
        if (isset($keyPair)) {
            $this->setKeyPair($keyPair);
        }
    }

    /**
     * Get the key pair in use
     *
     * @return \CvPls\Build\KeyPair The cryptographic key pair
     */
    public function getKeyPair()
    {
        return $this->keyPair;
    }

    /**
     * Set the key pair in use
     *
     * @param \CvPls\Build\KeyPair $keyPair The cryptographic key pair
     */
    public function setKeyPair(KeyPair $keyPair)
    {
        $this->keyPair = $keyPair;
    }

    /**
     * Sign a file using the current key pair
     *
     * @param string     $filePath Path to the file
     * @param string|int $algo     Algorithm to use for the signing operation
     *
     * @return string The signature
     *
     * @throws \InvalidArgumentException When the file path does not exist
     * @throws \RuntimeException         When the file path cannot be read
     */
    public function signFile($filePath, $algo = \OPENSSL_ALGO_SHA1)
    {
        if (!is_file($filePath)) {
            throw new \InvalidArgumentException('The specified file does not exist');
        } else if (!$data = file_get_contents($filePath)) {
            throw new \RuntimeException('The specified file could not be read');
        }

        return $this->signString($data, $algo);
    }

    /**
     * Sign a string of data using the current key pair
     *
     * @param string     $data The data
     * @param string|int $algo Algorithm to use for the signing operation
     *
     * @return string The signature
     */
    public function signString($data, $algo = \OPENSSL_ALGO_SHA1)
    {
        if (!openssl_sign($data, $signature, $this->keyPair->getPrivateKey(KeyPair::FORMAT_RES), $algo)) {
            throw new \InvalidArgumentException('An error occured while generating the data signature');
        }

        return $signature;
    }
}
