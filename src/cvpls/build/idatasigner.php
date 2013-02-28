<?php

  namespace CvPls\Build;

  interface IDataSigner {

    const FORMAT_PEM = 1;
    const FORMAT_DER = 2;

    public function loadKeysFromFile($filePath);
    public function loadKeysFromString($pemData);
    public function freeKeys();

    public function getPrivateKey($format = self::FORMAT_PEM);
    public function getPublicKey($format = self::FORMAT_PEM);

    public function signFile($filePath, $algo = OPENSSL_ALGO_SHA1);
    public function signString($data, $algo = OPENSSL_ALGO_SHA1);

  }
