<?php
/**
 * Factory which makes cryptographic key pair objects
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
 * Factory which makes cryptographic key pair objects
 *
 * @category [cv-pls]
 * @package  Build
 * @author   Chris Wright <info@daverandom.com>
 */
class KeyPairFactory
{
    /**
     * Create a key pair instance
     *
     * @return \CvPls\Build\KeyPair The created instance
     */
    public function create($pemFile = null)
    {
        return new KeyPair($pemFile);
    }
}
