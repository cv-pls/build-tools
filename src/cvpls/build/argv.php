<?php

  namespace CvPls\Build;

  class Argv {

    /**
     * @var array The raw parsed argument arrays
     */
    private $raw = [];

    /**
     * @var array Cache of unified argument arrays
     */
    private $argCache = [];

    /**
     * @var array Cache of passed flags
     */
    private $flagCache = [];

    /**
     * @var int The number of raw arguments
     */
    private $count = 0;

    /**
     * Constructor
     *
     * @param string[] $argv Input array to parse
     */
    public function __construct(array $argv = null) {
      if (isset($argv)) {
        $this->parse($argv);
      }
    }

    /**
     * Parse $argv formatted array into internal data structures
     *
     * @param string[] $argv Input array
     */
    public function parse(array $argv) {
      $key = NULL;

      $this->count = max(count($argv) - 1, 0);

      for ($i = 1; isset($argv[$i]); $i++) {
        if ($argv[$i][0] === '-') {
          if (isset($key) && !isset($this->raw[$key])) {
            $this->flagCache[$key] = true;
          }

          $length = strlen($argv[$i]);
          if ($argv[$i][1] !== '-' && $length > 2) {
            for ($j = 1, $l = $length - 1; $j < $l; $j++) {
              $this->flagCache[$argv[$i][$j]] = true;
            }

            $key = $argv[$i][$j];
          } else if (strpos($argv[$i], '=') !== false) {
            list($key, $value) = explode('=', ltrim($argv[$i], '-'), 2);

            if (isset($this->raw[$key])) {
              $this->raw[$key][] = $value;
            } else if (isset($key)) {
              $this->raw[$key] = [$value];
            }

            $key = $value = null;
          } else {
            $key = ltrim($argv[$i], '-');
          }
        } else if (isset($this->raw[$key])) {
          $this->raw[$key][] = $argv[$i];
        } else if (isset($key)) {
          $this->raw[$key] = [$argv[$i]];
        }
      }
      if (isset($key) && !isset($this->raw[$key])) {
        $this->flagCache[$key] = true;
      }
    }

    /**
     * Get an argument by name
     *
     * @param string|string[] $name    Name or list of synonyms
     * @param mixed           $default The default value to store if argument was not passed
     *
     * @return array List of values for the specified argument
     */
    public function getArg($name, $default = null) {
      $ref = [];

      foreach ((array) $name as $switch) {
        $switch = ltrim($switch, '-');

        if (isset($this->argCache[$switch])) {
          return $this->argCache[$switch];
        } else {
          $this->argCache[$switch] = &$ref;

          if (isset($this->raw[$switch])) {
            $ref = array_merge($ref, $this->raw[$switch]);
          }

          unset($this->raw[$switch]);
        }
      }

      if ($ref === []) {
        $ref = (array) $default;
      }

      return $ref;
    }

    /**
     * Check is an argument was passed
     *
     * @param string|string[] $name Name or list of synonyms
     *
     * @return bool Whether the argument was passed
     */
    public function hasFlag($name) {
      $result = false;

      foreach ((array) $name as $switch) {
        $switch = ltrim($switch, '-');

        if (isset($this->flagCache[$switch])) {
          $result = true;
          break;
        }

        if (isset($this->raw[$switch]) || isset($this->argCache[$switch])) {
          $result = $this->flagCache[$switch] = true;
          break;
        }
      }

      return $result;
    }

    /**
     * Get the number of raw arguments
     *
     * @return int The number of arguments
     */
    public function numArgs() {
      return $this->count;
    }

  }
