<?php
/**
 * Factory which makes Arguments objects
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
 * Factory which makes Arguments objects
 *
 * @category [cv-pls]
 * @package  Build
 * @author   Chris Wright <info@daverandom.com>
 */
class ArgumentsFactory
{
    /**
     * Create an arguments object
     *
     * @return \CvPls\Build\Arguments The created arguments object
     */
    public function create($platform, $keyFile, $baseDir, $version, $outFile, $manifestFile, $url, $updateUrl)
    {
        return new Arguments($platform, $keyFile, $baseDir, $version, $outFile, $manifestFile, $url, $updateUrl);
    }
}
