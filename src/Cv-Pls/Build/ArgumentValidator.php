<?php
/**
 * Validate command line arguments
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
 * Validate command line arguments
 *
 * @category [cv-pls]
 * @package  Build
 * @author   Chris Wright <info@daverandom.com>
 */
class ArgumentValidator
{
    /**
     * @var \CvPls\Build\ArgvParser Command line argument parser
     */
    private $argvParser;

    /**
     * @var \CvPls\Build\ArgumentsFactory Arguments factory object
     */
    private $argumentsFactory;

    /**
     * @var \CvPls\Build\Loggable Logging object
     */
    private $logger;

    /**
     * Constructor
     *
     * @param \CvPls\Build\ArgvParser $argv Command line argument parser
     */
    public function __construct(ArgvParser $argvParser, ArgumentsFactory $argumentsFactory, Loggable $logger = null)
    {
        $this->setArgvParser($argvParser);
        $this->setArgumentsFactory($argumentsFactory);

        if (isset($logger)) {
            $this->setLogger($logger);
        }
    }

    /**
     * Log a message
     *
     * @param string $message Message to log
     */
    private function log($message)
    {
        if (isset($this->logger)) {
            $this->logger->log($message . "\n");
        }
    }

    /**
     * Test that a file can be opened successfully in read/write mode
     *
     * @param string $path Path to test
     *
     * @return bool Whether the file can be opened in read/write mode
     */
    private function openAndTruncateFile($path)
    {
        if (!$fp = @fopen($path, 'w+')) {
            return false;
        }

        @fclose($fp);
        return true;
    }

    /**
     * Get the target platform from the raw arguments
     *
     * @return string The target platform identifier
     *
     * @throws \LogicException When no platform or more than one platform is specified
     */
    private function getPlatform()
    {
        if ($this->argvParser->hasFlag(['chrome'])) {
            $platform = 'chrome';
        }
        if ($this->argvParser->hasFlag(['mozilla'])) {
            if (isset($platform)) {
                throw new \LogicException('You must specify only one target platform');
            }

            $platform = 'mozilla';
        }

        if (!isset($platform)) {
            throw new \LogicException('You must specify a target platform with the --chrome or --mozilla flags');
        }

        return $platform;
    }

    /**
     * Get the target platform from the raw arguments
     *
     * @return string The target platform identifier
     *
     * @throws \LogicException When no platform or more than one platform is specified
     */
    private function getPlatform()
    {
        $this->log('Detecting platform...');

        if ($this->argvParser->hasFlag(['chrome'])) {
            $platform = 'chrome';
        }
        if ($this->argvParser->hasFlag(['mozilla'])) {
            if (isset($platform)) {
                throw new \LogicException('You must specify only one target platform');
            }

            $platform = 'mozilla';
        }

        if (!isset($platform)) {
            throw new \LogicException('You must specify a target platform with the --chrome or --mozilla flags');
        }

        $this->log('Platform detected: '.$platform);

        return $platform;
    }

    /**
     * Get the path to the private key file from the raw arguments
     *
     * @return string The path to the private key file
     *
     * @throws \LogicException When no path was specified, or the path is not valid
     */
    private function getKeyFile()
    {
        $this->log('Validating private key file...');

        if (!$keyFile = current($args->getArg(['k', 'key']))) {
            throw new \LogicException('No private key specified, you must use the -k/--key option');
        }
        if (!is_file($keyFile) || !is_readable($keyFile)) {
            throw new \LogicException('The specified private key file does not exist or not readable');
        }

        $this->log('Private key file detected successfully');

        return $keyFile;
    }

    /**
     * Get the path to the package source base directory
     *
     * @return string The path to the package source base directory
     *
     * @throws \LogicException When the path is not valid
     */
    private function getBaseDir()
    {
        $this->log('Locating package source base directory...');

        $baseDir = current($args->getArg(['d', 'base-dir'], getcwd()));
        if (!is_dir($baseDir) || !is_readable($baseDir)) {
            throw new \LogicException('The specified base directory does not exist or not readable');
        }

        $this->log('Base directory detected successfully');

        return $baseDir;
    }

    /**
     * Get the package version to use
     *
     * @return string|null The package version to use
     */
    private function getVersion()
    {
        $this->log('Detecting package version...');

        $version = current($args->getArg(['v', 'version']));
        if ($version !== false) {
            $this->log('Package version detected successfully: ' . $version);
        } else {
            $version = null;
            $this->log('Package version will be read from package manifest');
        }

        return $version;
    }

    /**
     * Get the path for the output package binary
     *
     * @param string $version The package version
     *
     * @return string|null The path for the output package binary
     */
    private function getOutputFilePath($version)
    {
        $this->log('Detecting output file path...');

        $outFile = current($args->getArg(['o', 'out-file']));
        if ($outFile !== false) {
            if (file_exists($outFile) && !$args->hasFlag(['f', 'force'])) {
                throw new \LogicException('The specified output file already exists, use the -f/--force option to overwrite');
            } else if (!$this->openAndTruncateFile($outFile)) {
                throw new \LogicException('Unable to open output file in read/write mode');
            }

            $this->log('Output file path detected successfully: ' . $outFile);
        } else {
            $outFile = null;
            $this->log('Output file path will be derived from package version.');
        }

        return $outFile;
    }

    /**
     * Get the path for the manifest file
     *
     * @return string|null The path for the manifest file
     */
    private function getManifestFilePath()
    {
        if ($args->hasFlag(['n', 'no-manifest'])) {
            return false;
        }
    
        $this->log('Detecting manifest file path...');

        $manifestFile = current($args->getArg(['m', 'manifest']));
        if ($manifestFile !== false) {
            if (!$this->openAndTruncateFile($manifestFile)) {
                throw new \LogicException('Unable to open manifest file in read/write mode');
            }

            $this->log('Manifest file path detected successfully: ' . $manifestFile);
        } else {
            $manifestFile = null;
            $this->log('Manifest file path will be derived from platform.');
        }

        return $manifestFile;
    }

    /**
     * Get the package URL for the manifest file
     *
     * @param string $manifestFile The path for the manifest file
     *
     * @return string|null The package URL for the manifest file
     */
    private function getUrl($manifestFile)
    {
        if ($manifestFile === false) {
            return;
        }
    
        $this->log('Detecting URL for manifest file...');

        $url = current($args->getArg(['u', 'url']));
        if ($url === false) {
            throw new \LogicException('Unable to detect manifest URL');
        }

        $this->log('URL detected successfully: ' . $url);

        return $url;
    }

    /**
     * Get the argv parser
     *
     * @return \CvPls\Build\ArgvParser Command line argument parser
     */
    public function getArgvParser()
    {
        return $this->argvParser;
    }

    /**
     * Set the argv parser
     *
     * @param \CvPls\Build\ArgvParser $argvParser Command line argument parser
     */
    public function setArgvParser(ArgvParser $argvParser)
    {
        $this->argvParser = $argvParser;
    }

    /**
     * Get the arguments factory object
     *
     * @return \CvPls\Build\ArgumentsFactory Arguments factory object
     */
    public function getArgumentsFactory()
    {
        return $this->argumentsFactory;
    }

    /**
     * Set the arguments factory object
     *
     * @param \CvPls\Build\ArgumentsFactory $argumentsFactory Arguments factory object
     */
    public function setArgumentsFactory(ArgumentsFactory $argumentsFactory)
    {
        $this->argumentsFactory = $argumentsFactory;
    }

    /**
     * Get the logger
     *
     * @return \CvPls\Build\Loggable The logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Set the logger
     *
     * @param \CvPls\Build\Loggable $logger The logger
     */
    public function setLogger(Loggable $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Validate command line arguments
     *
     * @return \CvPls\Build\Arguments The validated arguments
     */
    public function validate()
    {
        $platform     = $this->getPlatform();
        $keyFile      = $this->getKeyFile();
        $baseDir      = $this->getBaseDir();
        $version      = $this->getVersion();
        $outFile      = $this->getOutputFilePath($version);
        $manifestFile = $this->getManifestFilePath();
        $url          = $this->getUrl($manifestFile);

        return $this->argumentsFactory->create($platform, $keyFile, $baseDir, $version, $outFile, $manifestFile, $url);
    }
}
