#!/usr/bin/php
<?php

  use \CvPls\Build;

  require __DIR__.'/autoload.php';

  error_reporting(0);
  ini_set('display_errors', 0);

  function open_and_truncate_file($path) {
    if (!$fp = @fopen($path, 'w+')) {
      return false;
    }

    @fclose($fp);
    return true;
  }

  function error($message) {
    fwrite(STDERR, "ERROR: $message\n");
    exit;
  }

  // Process arguments

  $args = new Build\Argv($argv);

  if ($args->numArgs() === 0 || $args->hasFlag(['help'])) {
    echo <<<HELP
Builds a Chrome extension package of the cv-pls plugin

Usage: build.php -[fn] -k <keyfile> [options]                                 |

 Options:

  -k, --key         Required. Path to PEM encoded private key file.
  -o, --out-file    Path to output .crx file. Defaults to cv-pls_{version}.crx
                    in the current working directory.
  -f, --force       Force overwriting the output file path if it exists.
  -v, --version     Force overwriting the output file path if it exists.
  -d, --base-dir    Path to plugin base directory. Defaults to the relative
                    path within the GitHub repository from which this script
                    was obtained.
  -m, --manifest    Path to update manifest file. Defaults to update.xml in
                    the current working directory.
  -n, --no-manifest Do not create an update manifest file.
  -u, --url         Full URL of the .crx file. Use a single %s argument for
                    the filename component of the output file. Defaults to:
                    https://cv-pls.{hostname}/%s - the default value requires
                    an internet connection and PTR record to resolve your
                    public IP address to a hostname

HELP;
  }

  $defaultBaseDir = __DIR__.'/../src';
  $defaultOutFile = "cv-pls_%s.crx";
  $defaultManifestFile = 'update.xml';

  $ipResolver = 'http://wtfismyip.com/text';

  echo "Loading private key...";
  $keyFile = current($args->getArg(['k', 'key']));
  if (!$keyFile) {
    echo " Failed\n";
    error('No private key specified, you must use the -k/--key option');
  } else if (!is_file($keyFile) || !is_readable($keyFile)) {
    echo " Failed\n";
    error('The specified private key file does not exist or not readable');
  }
  $dataSigner = new Build\DataSigner($keyFile);
  echo " Done\n";


  echo "Checking package info...";
  $baseDir = current($args->getArg(['d', 'base-dir'], $defaultBaseDir));

  $defaultVersion = json_decode(file_get_contents($baseDir . '/manifest.json'))->version;
  $version = current($args->getArg(['v', 'version'], $defaultVersion));
  echo " Done\n";

  echo "Resolving paths...";
  $outFile = current($args->getArg(['o', 'out-file'], sprintf($defaultOutFile, $version)));
  if (!$args->hasFlag(['f', 'force']) && file_exists($outFile)) {
    echo " Failed\n";
    error('The specified output file already exists, use the -f/--force option to overwrite');
  } else if (!open_and_truncate_file($outFile)) {
    echo " Failed\n";
    error('Unable to open output file in read/write mode');
  }
  @fclose($fp);

  if (!$args->hasFlag(['n', 'no-manifest'])) {
    $manifestFile = current($args->getArg(['m', 'manifest'], $defaultManifestFile));
    if (!open_and_truncate_file($manifestFile)) {
      echo " Failed\n";
      error('Unable to open manifest file in read/write mode');
    }

    if ($args->hasFlag(['u', 'url'])) {
      $crxUrl = current($args->getArg(['u', 'url']));
    } else {
      $ipv4Expr = '/(?:(?:^|\.)(?:\d?\d|1\d\d|2[0-4]\d|25[0-5])){4}$/';
      if ((!$myIp = trim(@file_get_contents($ipResolver))) || !preg_match($ipv4Expr, $myIp)) {
        echo " Failed\n";
        error('Unable to resolve public IP address, consider using -u/-url to specify an absolute URL');
      }

      if (!$host = gethostbyaddr($myIp)) {
        $host = $myIp;
      } else if ($host !== $myIp) {
        $host = 'cv-pls.'.$host;
      }

      $crxUrl = "https://{$host}/%s";
    }

    $crxUrl = sprintf($crxUrl, basename($outFile));
  }
  echo " Done\n";

  echo "Building crx package...";
  $crx = new Build\Chrome\CRXFile($dataSigner);
  $crx->open($outFile);

  if ($args->hasFlag(['v', 'version'])) {
    $files = glob("$baseDir/*");
    foreach ($files as $file) {
      if (basename($file) !== 'manifest.json') {
        if (is_dir($file)) {
          $crx->addDir($file);
        } else {
          $crx->addFile($file);
        }
      }
    }

    $manifest = json_decode(file_get_contents($baseDir . '/manifest.json'));
    $manifest->version = $version;
    $crx->addFromString('manifest.json', json_encode($manifest));
  } else {
    $crx->addDirContents($baseDir);
  }

  $crx->close();
  echo " Done\n";

  if (!$args->hasFlag(['n', 'no-manifest'])) {
    echo "Building update manifest...";
    $appIdGenerator = new Build\Chrome\AppIdGenerator($dataSigner);
    $updateManifest = new Build\Chrome\UpdateManifest($appIdGenerator, $crxUrl, $version);
    $updateManifest->generate();
    $updateManifest->save($manifestFile);
    echo " Done\n";
  }