<?php
/**
 * Interface for logger objects
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
 * Interface for logger objects
 *
 * @category [cv-pls]
 * @package  Build
 * @author   Chris Wright <info@daverandom.com>
 */
interface Loggable
{
    /**
     * Log a message
     *
     * @param string $message The message
     */
    public function log($message);

    /**
     * Log an error
     *
     * @param string $message The error message
     */
    public function error($message);
}
