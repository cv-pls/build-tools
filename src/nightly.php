<?php

  $history = 5;
  $baseUrl = 'https://cv-pls.pieterhordijk.com/';
  $branch = 'alpha';

  chdir(dirname(dirname(__DIR__)));

  function run($cmd) {
    echo "$cmd\n";
    $out = shell_exec($cmd . ' 2>&1');
    echo $out;
    return $out;
  }

  // Chrome
  $gitBase = 'git';
  $currentCommit = null;
  if (!file_exists('chrome-cv-pls')) {
    run("$gitBase clone git://github.com/cv-pls/chrome-cv-pls");
    chdir('chrome-cv-pls');
    run("$gitBase checkout $branch");
    run("$gitBase submodule update --init");
  } else {
    chdir('chrome-cv-pls');
    run("$gitBase checkout $branch");
    $currentCommit = trim(run("$gitBase rev-parse HEAD"));
    run("$gitBase pull");
    run("$gitBase submodule update");
  }
  $newCommit = trim(run("$gitBase rev-parse HEAD"));
  chdir('..');

  $doBuild = false;
  $versionIncrement = 0;
  $existing = [];
  $basePath = 'site/public/chrome/dev';

  foreach (glob($basePath . '/cv-pls_*') as $file) {
    if (preg_match('/^cv-pls_\d+\.\d+\.\d+\.(\d+)\.crx$/', basename($file), $matches)) {
      if ($matches[1] > $versionIncrement) {
        $versionIncrement = (int) $matches[1];
      }

      $existing[(int) $matches[1]] = $file;
    }
  }

  if ($newCommit !== $currentCommit || !$existing) {
    $versionIncrement++;
    $chromeVersion = json_decode(file_get_contents('chrome-cv-pls/src/manifest.json'))->version . '.' . $versionIncrement;
    run(
        'php build-tools/src/build.php --chrome -f'
      . ' -k build-tools/chrome.pem'
      . ' -o site/public/chrome/dev/cv-pls_' . $chromeVersion . '.crx'
      . ' -m site/public/chrome/dev/update.xml'
      . ' -v ' . $chromeVersion
      . ' -d chrome-cv-pls/src'
      . ' -u ' . $baseUrl . 'chrome/dev/cv-pls_' . $chromeVersion . '.crx'
      . ' -p ' . $baseUrl . 'update/chrome?branch=' . $branch
    );

    foreach ($existing as $file) {
      unlink($file);
    }
  }



  // Mozilla
  $gitBase = 'git';
  $currentCommit = null;
  if (!file_exists('ff-cv-pls')) {
    run("$gitBase clone git://github.com/cv-pls/ff-cv-pls");
    chdir('ff-cv-pls');
    run("$gitBase checkout $branch");
    run("$gitBase submodule update --init");
  } else {
    chdir('ff-cv-pls');
    run("$gitBase checkout $branch");
    $currentCommit = trim(run("$gitBase rev-parse HEAD"));
    run("$gitBase pull");
    run("$gitBase submodule update");
  }
  $newCommit = trim(run("$gitBase rev-parse HEAD"));
  chdir('..');

  $doBuild = false;
  $versionIncrement = 0;
  $existing = [];
  $basePath = 'site/public/mozilla/dev';

  foreach (glob($basePath . '/cv-pls_*') as $file) {
    if (preg_match('/^cv-pls_\d+\.\d+\.\d+\.(\d+)\.xpi$/', basename($file), $matches)) {
      if ($matches[1] > $versionIncrement) {
        $versionIncrement = (int) $matches[1];
      }

      $existing[(int) $matches[1]] = $file;
    }
  }

  if ($newCommit !== $currentCommit || !$existing) {
    $versionIncrement++;

    $installRdf = new \DOMDocument;
    $installRdf->load('ff-cv-pls/src/install.rdf');
    $installRdfXpath = new \DOMXpath($installRdf);
    $installRdfXpath->registerNamespace('em', 'http://www.mozilla.org/2004/em-rdf#');
    $mozillaVersion = $installRdfXpath->query('//em:version')->item(0)->firstChild->data . '.' . $versionIncrement;

    run(
        'php build-tools/src/build.php --mozilla -f'
      . ' -k build-tools/mozilla.pem'
      . ' -o site/public/mozilla/dev/cv-pls_' . $mozillaVersion . '.xpi'
      . ' -m site/public/mozilla/dev/update.rdf'
      . ' -v ' . $mozillaVersion
      . ' -d ff-cv-pls/src'
      . ' -u ' . $baseUrl . 'mozilla/dev/cv-pls_' . $mozillaVersion . '.xpi'
      . ' -p ' . $baseUrl . 'update/mozilla?branch=' . $branch
    );
    
    foreach ($existing as $file) {
      unlink($file);
    }
  }

  if (isset($mozillaVersion) || isset($chromeVersion)) {
    require 'site/config.downloads.php';

    $fileContent = "<?php
    define('STABLE_VERSION', '".STABLE_VERSION."');";

    if (defined('DEVELOPMENT_VERSION')) {
      $fileContent .= "
    define('DEVELOPMENT_VERSION', '".DEVELOPMENT_VERSION."');";
    } else {
      $fileContent .= "
    //define('DEVELOPMENT_VERSION', '');";
    }

    if (isset($chromeVersion)) {
      $fileContent .= "
    define('CHROME_ALPHA_VERSION', '".$chromeVersion."');";
    } else {
      $fileContent .= "
    //define('CHROME_ALPHA_VERSION', '');";
    }

    if (isset($mozillaVersion)) {
      $fileContent .= "
    define('MOZILLA_ALPHA_VERSION', '".$mozillaVersion."');";
    } else {
      $fileContent .= "
    //define('MOZILLA_ALPHA_VERSION', '');";
    }    

    file_put_contents('site/config.downloads.php', $fileContent);
  }