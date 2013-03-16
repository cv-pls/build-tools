<?php

    function writeFile($outputFile, $path, &$jsLint, &$globals)
    {
        $fp = fopen($path, 'r');

        while (trim($line = fgets($fp)) !== '/**') {
          if (preg_match('#^/\*jslint\s*(.*?)\s*\*/#', $line, $matches)) {
              $parts = preg_split('/\s*,\s*/', $matches[1]);

              foreach ($parts as $part) {
                  list($k, $v) = preg_split('/\s*:\s*/', $part);
                  $jsLint[trim($k)] = trim($v);
              }
          } else if (preg_match('#^/\*global\s*(.*?)\s*\*/#', $line, $matches)) {
              $globals = array_merge($globals, preg_split('/\s*,\s*/', $matches[1]));
          }
        }

        $file = '';

        do {
            $file .= rtrim('    ' . $line) . "\n";
            $line = fgets($fp);
        } while (!feof($fp));

        fwrite($outputFile, rtrim($file) . "\n\n");
    }

    if (!isset($argv[1])) {
        exit("
Usage: php build-module.php /path/to/dir
");
    }

    $baseDir = rtrim(str_replace('\\', '/', realpath($argv[1])), '/');

    $moduleFile = $baseDir . '/module.js';
    if (!is_file($moduleFile)) {
        exit("Directory $baseDir does not contain a module.js file\n");
    }

    $classFiles = $jsLint = $globals = [];

    $exclude = ['module.js', 'helpers.js', 'bootstrap.js', basename($baseDir) . '.js'];
    foreach (glob($baseDir . '/*.js') as $file) {
        if (!in_array(basename($file), $exclude)) {
            $classFiles[pathinfo($file, PATHINFO_FILENAME)] = $file;
        }
    }

    $tempStream = fopen('php://temp', 'w+');

    fwrite($tempStream, "(function() {\n\n    'use strict';\n\n    var ");

    $pos = 8;
    $last = count($classFiles) - 1;
    foreach (array_keys($classFiles) as $i => $class) {
        if ($pos + strlen($class) > 100) {
            fwrite($tempStream, "\n        ");
            $pos = 8;
        }

        $terminator = $i == $last ? ';' : ', ';
        fwrite($tempStream, $class . $terminator);
    }
    fwrite($tempStream, "\n\n");

    if (is_file($baseDir . '/helpers.js')) {
        writeFile($tempStream, $baseDir . '/helpers.js', $jsLint, $globals);
    }

    foreach ($classFiles as $file) {
        writeFile($tempStream, $file, $jsLint, $globals);
    }

    writeFile($tempStream, $moduleFile, $jsLint, $globals);

    if (is_file($baseDir . '/bootstrap.js')) {
        writeFile($tempStream, $baseDir . '/bootstrap.js', $jsLint, $globals);
    }

    fwrite($tempStream, "}());\n");
    rewind($tempStream);

    $outputPath = $baseDir . '/' . basename($baseDir) . '.js';
    if (!$outputFile = fopen($outputPath, 'w')) {
        exit("Unable to open output file $outputFile");
    }

    if ($jsLint) {
        foreach ($jsLint as $k => $v) {
            $jsLint[$k] = "$k: $v";
        }

        fwrite($outputFile, "/*jslint ".implode(', ', $jsLint)." */\n");
    }

    $globals = array_diff(array_unique($globals), array_keys($classFiles));
    if ($globals) {
        sort($globals);
        fwrite($outputFile, "/*global ".implode(', ', $globals)." */\n");
    }

    fwrite($outputFile, "/* Built with build-module.php at ".gmdate('Y-m-d H:i:s')." GMT */\n\n");

    stream_copy_to_stream($tempStream, $outputFile);
