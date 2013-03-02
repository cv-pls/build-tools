<?php

namespace CvPls\Build;

class KeyPair
{
    /**
     * @const int Return the key as an ext/openssl resource
     */
    const FORMAT_RES = 1;

    /**
     * @const int Return the key as PEM encoded string
     */
    const FORMAT_PEM = 2;

    /**
     * @const int Return the key as DER encoded string
     */
    const FORMAT_DER = 3;

    /**
     * @var string PEM encoded private key
     */
    private $privateKeyPEM;

    /**
     * @var resource ext/openssl resource for the private key
     */
    private $privateKeyResource;

    /**
     * @var string PEM encoded public key
     */
    private $publicKeyPEM;

    /**
     * @var resource ext/openssl resource for the public key
     */
    private $publicKeyResource;

    /**
     * Convert a PEM formatted key to DER format
     *
     * @param string $pem PEM formatted key
     *
     * @return string DER formatted key
     */
    private function pem2der($pem)
    {
        return base64_decode(implode('', array_slice(preg_split('/[\r\n]+/', trim($pem)), 1, -1)));
    }

    /**
     * Load data from a file and return it as a string
     *
     * @param string $filePath The path to the file
     *
     * @return string The file data
     *
     * @throws \InvalidArgumentException When the file path does not exist
     * @throws \RuntimeException         When the file path cannot be read
     */
    private function loadFile($filePath)
    {
        if (!is_file($filePath)) {
            throw new \InvalidArgumentException('The specified file does not exist');
        } else if (!$data = file_get_contents($filePath)) {
            throw new \RuntimeException('The specified file could not be read');
        }

        return $data;
    }

    /**
     * Constructor
     *
     * @param string $filePath The path to a PEM private key file
     *
     * @throws \InvalidArgumentException When the file path does not exist or does not contain a valid private PEM formatted key
     * @throws \RuntimeException         When the file path cannot be read
     */
    public function __construct($pemFile = NULL)
    {
        if ($pemFile !== NULL) {
            $this->loadKeysFromFile($pemFile);
        }
    }

    /**
     * Load a key pair from a PEM formatted file
     *
     * @param string $filePath The path to a PEM private key file
     *
     * @throws \InvalidArgumentException When the file path does not exist or does not contain a valid private PEM formatted key
     * @throws \RuntimeException         When the file path cannot be read
     */
    public function loadKeysFromFile($filePath)
    {
        $this->freeKeys();
        $this->loadKeysFromString($this->loadFile($filePath));
    }

    /**
     * Load a key pair from a PEM formatted string
     *
     * @param string $pemData The PEM formatted string
     *
     * @throws \InvalidArgumentException When the file path does not exist or does not contain a valid private PEM formatted key
     * @throws \RuntimeException         When the file path cannot be read
     */
    public function loadKeysFromString($pemData)
    {
        $this->freeKeys();

        if ((!$privateKeyResource = openssl_pkey_get_private($pemData)) || (!$details = openssl_pkey_get_details($privateKeyResource))) {
            throw new \InvalidArgumentException('The specified file does not contain a valid PEM formatted private key');
        }
        if (!$publicKeyResource = openssl_pkey_get_public($details['key'])) {
            throw new \InvalidArgumentException('Unable to extract public key');
        }

        $this->privateKeyPEM      = $pemData;
        $this->privateKeyResource = $privateKeyResource;
        $this->publicKeyPEM       = $details['key'];
        $this->publicKeyResource  = $publicKeyResource;
    }

    /**
     * Free the memory used by the currently loaded key pair
     */
    public function freeKeys()
    {
        if (is_resource($this->privateKeyResource)) {
            openssl_free_key($this->privateKeyResource);
        }
        if (is_resource($this->publicKeyResource)) {
            openssl_free_key($this->publicKeyResource);
        }

        $this->privateKeyResource = $this->publicKeyResource = $this->privateKeyPEM = $this->publicKeyPEM = null;
    }

    /**
     * Get the private key
     *
     * @param int $format One of the FORMAT_* constants
     *
     * @return resource|string The private key
     */
    public function getPrivateKey($format = self::FORMAT_PEM)
    {
        switch ($format) {
            case self::FORMAT_RES:
                return $this->privateKeyResource;

            case self::FORMAT_DER:
                return $this->pem2der($this->privateKeyPEM);

            case self::FORMAT_PEM:
            default:
                return $this->privateKeyPEM;
        }
    }

    /**
     * Get the public key
     *
     * @param int $format One of the FORMAT_* constants
     *
     * @return resource|string The public key
     */
    public function getPublicKey($format = self::FORMAT_PEM)
    {
        switch ($format) {
            case self::FORMAT_RES:
                return $this->publicKeyResource;

            case self::FORMAT_DER:
                return $this->pem2der($this->publicKeyPEM);

            case self::FORMAT_PEM:
            default:
                return $this->publicKeyPEM;
        }
    }
}
