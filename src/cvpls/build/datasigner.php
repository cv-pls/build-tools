<?php

  namespace CvPls\Build;

  class DataSigner implements IDataSigner {

    private $privateKeyResource;
    private $privateKeyPEM;
    private $publicKeyPEM;

    private function pem2der($pem) {
      return base64_decode(implode('', array_slice(preg_split('/[\r\n]+/', trim($pem)), 1, -1)));
    }
    private function loadFile($filePath) {
      if (!is_file($filePath)) {
        throw new \InvalidArgumentException('The specified file does not exist');
      } else if (!$data = file_get_contents($filePath)) {
        throw new \InvalidArgumentException('The specified file could not be read');
      }
      return $data;
    }

    public function __construct($pemFile = NULL) {
      if ($pemFile !== NULL) {
        $this->loadKeysFromFile($pemFile);
      }
    }

    public function loadKeysFromFile($filePath) {
      $this->freeKeys();
      $this->loadKeysFromString($this->loadFile($filePath));
    }
    public function loadKeysFromString($pemData) {
      $this->freeKeys();

      if ((!$privateKeyResource = openssl_pkey_get_private($pemData)) || (!$details = openssl_pkey_get_details($privateKeyResource))) {
        throw new \InvalidArgumentException('The specified file does not contain a valid PEM formatted private key');
      }

      $this->privateKeyResource = $privateKeyResource;
      $this->privateKeyPEM = $pemData;
      $this->publicKeyPEM = $details['key'];
    }
    public function freeKeys() {
      if (is_resource($this->privateKeyResource)) {
        openssl_free_key($this->privateKeyResource);
      }
      $this->privateKeyResource = $this->privateKey = $this->publicKey = NULL;
    }

    public function getPrivateKey($format = self::FORMAT_PEM) {
      return $format & self::FORMAT_DER ? $this->pem2der($this->privateKeyPEM) : $this->privateKeyPEM;
    }
    public function getPublicKey($format = self::FORMAT_PEM) {
      return $format & self::FORMAT_DER ? $this->pem2der($this->publicKeyPEM) : $this->publicKeyPEM;
    }

    public function signFile($filePath, $algo = OPENSSL_ALGO_SHA1) {
      return $this->signString($this->loadFile($filePath), $algo);
    }
    public function signString($data, $algo = OPENSSL_ALGO_SHA1) {
      if (!openssl_sign($data, $signature, $this->privateKeyResource, $algo)) {
        throw new \InvalidArgumentException('An error occured while generating the data signature');
      }
      return $signature;
    }

  }
