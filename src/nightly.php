<?php

  $history = 5;
  $baseUrl = 'https://cv-pls.pieterhordijk.com/';

  chdir(dirname(dirname(__DIR__)));

  function run($cmd) {
    echo "$cmd\n";
    $out = shell_exec($cmd . ' 2>&1');
    echo $out;
    return $out;
  }

  // Deal with chrome
  chdir('chrome-cv-pls');
  $gitBase = 'git';
  run("$gitBase checkout dev");
  $currentCommit = trim(run("$gitBase rev-parse HEAD"));
  run("$gitBase pull");
  run("$gitBase submodule update");
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
    $version = json_decode(file_get_contents('chrome-cv-pls/src/manifest.json'))->version . '.' . $versionIncrement;
    run(
        'php build-tools/src/build.php --chrome -f'
      . ' -k chrome-cv-pls/chrome.pem'
      . ' -o site/public/chrome/dev/cv-pls_' . $version . '.crx'
      . ' -m site/public/chrome/dev/update.xml'
      . ' -v ' . $version
      . ' -d chrome-cv-pls/src'
      . ' -u ' . $baseUrl . 'chrome/dev/cv-pls_' . $version . '.crx'
    );
  }    