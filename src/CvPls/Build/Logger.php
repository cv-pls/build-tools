<?php
/**
 * Class for logging
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
 * Class for logging
 *
 * @category [cv-pls]
 * @package  Build
 * @author   Chris Wright <info@daverandom.com>
 */
class Logger implements Loggable
{
    /**
     * Log a message
     *
     * @param string $message The message
     */
    public function log($message)
    {
        fwrite(STDOUT, $message . "\n");
    }

    /**
     * Log an error
     *
     * @param string $message The error message
     */
    public function error($message)
    {
        fwrite(STDERR, "ERROR: " . $message . "\n");
    }
}
