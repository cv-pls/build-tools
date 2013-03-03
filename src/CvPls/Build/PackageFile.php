<?php
/**
 * Abstract class for package builders
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
 * Abstract class for package builders
 *
 * @category [cv-pls]
 * @package  Build
 * @author   Chris Wright <info@daverandom.com>
 */
abstract class PackageFile extends \ZipArchive
{
    /**
     * @var string Temporary directory path
     */
    private $tempDirectory;

    /**
     * @var bool Whether the file is open
     */
    private $isOpen = false;

    /**
     * @var bool Path to the temporary build file
     */
    private $tmpFile;

    /**
     * @var bool Path to the output file
     */
    private $outputFile;

    /**
     * Check if a path can be written to by checking if the deepest existing path is writable
     *
     * @param string $path Path to check
     *
     * @return bool Whether the path is writable
     */
    private function isReallyWritable($path)
    {
        if (is_writable($path)) {
            return true;
        }

        $dirname = str_replace('\\', '/', dirname($path)); // Windows converts / to \ - grrrr
        if ($dirname !== '' && $dirname !== $path) {
            return $this->isReallyWritable($dirname);
        }

        return false;
    }

    /**
     * Close and delete the temporary file and return the data as a string
     *
     * @return string The file data
     *
     * @throws \LogicException When the file is not open
     */
    protected function closeAndDestroyTempFile()
    {
        if (!$this->isOpen) {
            throw new \LogicException('Cannot close temp file, not open');
        }
        $this->isOpen = false;

        parent::close();
        $data = file_get_contents($this->tmpFile);
        unlink($this->tmpFile);

        return $data;
    }

    /**
     * Write data to the output file
     *
     * @param string $data The file data
     *
     * @throws \RuntimeException When the output file cannot be written
     */
    protected function writeOutputFile($data)
    {
        if (!file_put_contents($this->outputFile, $data)) {
            throw new \RuntimeException('Unable to write output file');
        }
    }

    /**
     * Constructor
     *
     * @param \CvPls\Build\DataSigner $dataSigner The DataSigner object
     * @param string                  $tempDirectory     Custom temporary directory path
     */
    public function __construct($tempDirectory = NULL)
    {
        if ($tempDirectory === NULL) {
            $tempDirectory = sys_get_temp_dir();
        }

        $this->settempDirectory($tempDirectory);
    }

    /**
     * Destructor calls close() if not already called
     */
    public function __destruct()
    {
        if ($this->isOpen) {
            $this->close();
        }
    }

    /**
     * Get the path of the temporary directory in use
     *
     * @return string The path of the temporary directory in use
     */
    public function getTempDirectory()
    {
        return $this->tempDirectory;
    }

    /**
     * Set the path of the temporary directory in use
     *
     * @param string $tempDirectory The temporary directory new path to use
     */
    public function setTempDirectory($tempDirectory)
    {
        $this->tempDirectory = $tempDirectory;
    }

    /**
     * Get the path of the output file in use
     *
     * @return string The path of the output file in use
     */
    public function getOutputFile()
    {
        return $this->outputFile;
    }

    /**
     * Set the path of the output file in use
     *
     * @param string $outputFile The new output file path to use
     */
    public function setOutputFile($outputFile)
    {
        $this->outputFile = $outputFile;
    }

    /**
     * Add a directory and its contents to the archive
     *
     * @param string $dir       The local filesystem path to the directory
     * @param string $localName The archive filesystem path to the directory
     *
     * @throws \RuntimeException When adding an object to the archive fails
     */
    public function addDir($dirPath, $localName = NULL)
    {
        if ($localName === NULL) {
          $localName = basename($dirPath);
        }
        if (!$this->addEmptyDir($localName)) {
            throw new \RuntimeException('Error adding directory '.$dirPath.' to archive');
        }
        $this->addDirContents($dirPath, $localName);
    }

    /**
     * Add the contents of a directory to the archive
     *
     * @param string $dir       The local filesystem path to the directory
     * @param string $localName The archive filesystem path to the directory
     *
     * @throws \RuntimeException When adding an object to the archive fails
     */
    public function addDirContents($dirPath, $localName = '')
    {
        $base = ltrim($localName.'/', '/');

        foreach (glob("$dirPath/*") as $file) {
            if (is_dir($file)) {
                $this->addDir($file, $base.basename($file));
            } else {
                if (!$this->addFile($file, $base.basename($file))) {
                    throw new \RuntimeException('Error adding file '.$file.' to archive');
                }
            }
        }
    }

    /**
     * Open the temporary file and store the output file path
     *
     * @param string $outputFile Output file path
     * @param int    $flags      \ZipArchive open flags
     *
     * @throws \InvalidArgumentException When the output file path or the temp path is not writable
     */
    public function open($outputFile = null, $flags = \ZIPARCHIVE::CREATE)
    {
        if (isset($outputFile)) {
            $this->setOutputFile($outputFile);
        }

        $this->tmpFile = $this->tempDirectory.'/'.uniqid().'.zip';
        if (is_file($this->tmpFile)) {
            unlink($this->tmpFile);
        }
        if (!$this->isReallyWritable($this->tmpFile)) {
            throw new \InvalidArgumentException('Temporary file path is not writable');
        }
        if (!is_dir(dirname($this->tmpFile))) {
            if (!mkdir(dirname($this->tmpFile), 0744, true)) {
                throw new \InvalidArgumentException('Temporary directory does not exist and could not be created');
            }
        }

        parent::open($this->tmpFile, $flags);
        $this->isOpen = true;
    }

    /**
     * Close the temporary file and transfer the data to the output file
     *
     * @throws \LogicException   When the file is not open
     * @throws \RuntimeException When the output file cannot be written
     */
    public function close()
    {
        $data = $this->closeAndDestroyTempFile();

        $this->writeOutputFile($data);
    }
}
