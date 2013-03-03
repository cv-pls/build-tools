#!/usr/bin/php
<?php

    use \CvPls\Build,
        \CvPls\Chrome,
        \CvPls\Mozilla;

    require __DIR__.'/autoload.php';

    //error_reporting(0);
    //ini_set('display_errors', 0);

    $logger = new Build\Logger;

    // Process arguments

    $argv = new Build\ArgvParser($argv);

    if ($argv->numArgs() === 0 || $argv->hasFlag(['help'])) {
        echo <<<HELP
Builds a Chrome extension package of the cv-pls plugin

Usage: build.php -[fn] -k <keyfile> [options]

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

    try {
        $validator = new Build\ArgumentValidator($argv, new Build\ArgumentsFactory, $logger);
        $arguments = $validator->validate();

        switch ($arguments->getPlatform()) {
            case 'chrome':
                $package = new Chrome\CRXPackage(
                    $arguments,
                    new Build\KeyPairFactory,
                    new Build\DataSignerFactory,
                    new Chrome\CRXFileFactory,
                    new Chrome\ChromeUpdateManifestFactory,
                    $logger
                );
                $package->validate();
                $package->build();
                break;

            case 'mozilla':
                break;
        }
    } catch (Exception $e) {
        $logger->error($e->getMessage());
    }
