<?php
/**
 * Chrome extension ID generator class
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

use \CvPls\Build\KeyPair;

/**
 * Chrome extension ID generator class
 *
 * @category [cv-pls]
 * @package  Chrome
 * @author   Chris Wright <info@daverandom.com>
 */
class IdGenerator
{
    /**
     * @var string[] Map of standard hex alphabet to Google's base16 alphabet
     */
    private $base16Alphabet = array(
        '0' => 'a', '1' => 'b', '2' => 'c', '3' => 'd',
        '4' => 'e', '5' => 'f', '6' => 'g', '7' => 'h',
        '8' => 'i', '9' => 'j', 'a' => 'k', 'A' => 'k',
        'b' => 'l', 'B' => 'l', 'c' => 'm', 'C' => 'm',
        'd' => 'n', 'D' => 'n', 'e' => 'o', 'E' => 'o',
        'f' => 'p', 'F' => 'p'
    );

    /**
     * @var \CvPls\Build\KeyPair Data signer object
     */
    private $keyPair;

    /**
     * Constructor
     *
     * @param \CvPls\Build\DataSigner $dataSigner Data signer object
     */
    public function __construct(KeyPair $keyPair = NULL)
    {
        if ($keyPair !== NULL) {
            $this->setKeyPair($keyPair);
        }
    }

    /**
     * Get the internal DataSigner object
     *
     * @return \CvPls\Build\DataSigner The internal DataSigner object
     */
    public function getKeyPair()
    {
        return $this->keyPair;
    }

    /**
     * Set the internal DataSigner object
     *
     * @param \CvPls\Build\KeyPair $keyPair The new DataSigner object
     */
    public function setKeyPair(KeyPair $keyPair)
    {
      $this->keyPair = $keyPair;
    }

    /**
     * Generate the extension ID from the public key
     *
     * @param \CvPls\Build\DataSigner $dataSigner The new DataSigner object
     */
    public function getAppId()
    {
        $key = $this->keyPair->getPublicKey(KeyPair::FORMAT_DER);

        $hash       = hash('sha256', $key, true);
        $first128   = substr($hash, 0, 16);
        $base16     = bin2hex($first128);
        $translated = strtr($base16, $this->base16Alphabet);

        return $translated;
    }
}
