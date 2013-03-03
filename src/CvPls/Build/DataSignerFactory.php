<?php
/**
 * Factory which makes data signer objects
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
 * Factory which makes data signer objects
 *
 * @category [cv-pls]
 * @package  Build
 * @author   Chris Wright <info@daverandom.com>
 */
class DataSignerFactory
{
    /**
     * Create a data signer instance
     *
     * @return \CvPls\Build\DataSigner The created instance
     */
    public function create(KeyPair $keyPair = null)
    {
        return new DataSigner($keyPair);
    }
}
