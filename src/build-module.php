<?php

    class SourceFile
    {
        private $constructorName;

        private $source = [];

        private $jsLint = [];

        private $globals = [];

        private $dependencies = [];

        private $vars = [];

        private function processAttrComment($comment)
        {
            if (preg_match('#^/\*jslint\s*(.*?)\s*\*/#', $comment, $matches)) {
                $parts = preg_split('/\s*,\s*/', $matches[1]);

                foreach ($parts as $part) {
                    list($k, $v) = preg_split('/\s*:\s*/', $part);
                    $this->jsLint[trim($k)] = trim($v);
                }
            } else if (preg_match('#^/\*global\s*(.*?)\s*\*/#s', $comment, $matches)) {
                preg_match_all('#([$\w]+)(?:\s*:\s*(true|false))?\s*(?:,|$)#i', $matches[1], $matches);

                foreach ($matches[1] as $i => $match) {
                    if (strtolower($matches[2][$i]) !== 'false') {
                        $this->globals[] = $match;
                    }
                }
            } else if (preg_match('#^/\*dependency\s*(.*?)\s*\*/#s', $comment, $matches)) {
                $this->dependencies = array_merge($this->dependencies, preg_split('/\s*,\s*/', $matches[1]));
            }
        }

        private function processVarDeclaration($vars)
        {
            $this->vars = array_merge($this->vars, preg_split('/\s*,\s*/', trim($vars)));
        }

        private function loadFileData($data)
        {
            $expr = '#^\s*(?:(/\*[^*]+?\*/)|(?:var([^;]+);)|(//[^\r\n]*\r?\n))#';

            while (preg_match($expr, $data, $matches)) {
                $data = ltrim(substr($data, strlen($matches[0])));

                if (!empty($matches[1])) {
                    $this->processAttrComment($matches[1]);
                } else if (!empty($matches[2])) {
                    $this->processVarDeclaration($matches[2]);
                }
            }

            foreach (preg_split('/\r?\n/', trim($data)) as $line) {
                $this->source[] = rtrim('    ' . $line);
            }
            $this->source[] = '';
        }

        public function __construct($filePath)
        {
            if (!is_file($filePath)) {
                throw new \InvalidArgumentException('File path ' . $filePath . ' is invalid');
            } else if (false === $data = @file_get_contents($filePath)) {
                throw new \RuntimeException('Unable to open ' . $filePath . ' for reading: ' . error_get_last()['message']);
            }

            $this->constructorName = pathinfo($filePath, PATHINFO_FILENAME);

            $this->loadFileData(trim($data));
        }

        public function getConstructorName()
        {
            return $this->constructorName;
        }

        public function getJSLint()
        {
            return $this->jsLint;
        }

        public function getGlobals()
        {
            return $this->globals;
        }

        public function getDependencies()
        {
            return $this->dependencies;
        }

        public function getVars()
        {
            return $this->vars;
        }

        public function hasDependency(SourceFile $file)
        {
            return in_array($file->getConstructorName(), $this->dependencies);
        }

        public function write($fp)
        {
            foreach ($this->source as $line) {
                fwrite($fp, $line . "\n");
            }
        }
    }

    if (!isset($argv[1])) {
        exit("\nUsage: php build-module.php /path/to/dir\n");
    }

    $baseDir = rtrim(str_replace('\\', '/', realpath($argv[1])), '/');
    if (!is_dir($baseDir)) {
        exit("Supplied argument is not a valid directory\n");
    }

    $moduleFile = $baseDir . '/module.js';
    if (!is_file($moduleFile)) {
        exit("Directory $baseDir does not contain a module.js file\n");
    }

    $outputPath = $baseDir . '/' . basename($baseDir) . '.js';
    if (!$outputFile = fopen($outputPath, 'w')) {
        exit("Unable to open output file $outputFile\n");
    }

    $classFiles = $jsLint = $globals = $vars = [];
    $helpers = $bootstrap = null;

    $exclude = ['module.js', 'helpers.js', 'bootstrap.js', basename($baseDir) . '.js'];
    foreach (glob($baseDir . '/*.js') as $file) {
        if (!in_array(basename($file), $exclude)) {
            $file = new SourceFile($file);
            $classFiles[$file->getConstructorName()] = $file;
            $jsLint  = array_merge($jsLint,  $file->getJSLint());
            $globals = array_merge($globals, $file->getGlobals());
            $vars    = array_merge($vars,    $file->getVars());
        }
    }

    uasort($classFiles, function($a, $b) {
        if ($a->hasDependency($b)) {
            return 1;
        } else if ($b->hasDependency($a)) {
            return -1;
        }

        return 0;
    });
    $files = [];
    foreach ($classFiles as $name => $file) {
        $files[] = $name;
        foreach ($file->getDependencies() as $dependency) {
            if (!in_array($dependency, $files)) {
                exit('Dependency conflict: ' . $name . ' depends on ' . $dependency . ' but appears later in the list');
            }
        }
    }

    $classNames = array_keys($classFiles);
    sort($classNames);

    if (is_file($baseDir . '/helpers.js')) {
        $helpers = new SourceFile($baseDir . '/helpers.js');
        $jsLint  = array_merge($jsLint,  $helpers->getJSLint());
        $globals = array_merge($globals, $helpers->getGlobals());
        $vars    = array_merge($vars,    $helpers->getVars());
    }
    if (is_file($baseDir . '/bootstrap.js')) {
        $bootstrap = new SourceFile($baseDir . '/bootstrap.js');
        $jsLint  = array_merge($jsLint,  $bootstrap->getJSLint());
        $globals = array_merge($globals, $bootstrap->getGlobals());
        $vars    = array_merge($vars,    $bootstrap->getVars());
    }
    $module = new SourceFile($moduleFile);
    $jsLint  = array_merge($jsLint,  $module->getJSLint());
    $globals = array_merge($globals, $module->getGlobals());
    $vars    = array_merge($vars,    $module->getVars());

    unset($jsLint['sloppy']);
    foreach ($jsLint as $k => $v) {
        $jsLint[$k] = "$k: $v";
    }

    $globals = array_diff(array_unique($globals), $classNames);
    sort($globals);

    $vars = array_unique(array_merge($vars, $classNames));
    sort($vars);

    if ($jsLint) {
        fwrite($outputFile, "/*jslint ".implode(', ', $jsLint)." */\n");
    }
    if ($globals) {
        fwrite($outputFile, "/*global ".implode(', ', $globals)." */\n");
    }
    fwrite($outputFile, "/* Built with build-module.php at " . date('r') . " */\n\n");

    fwrite($outputFile, "(function() {\n\n    'use strict';\n\n    var ");

    $pos = 8;
    $last = count($vars) - 1;
    foreach ($vars as $i => $var) {
        if ($pos + strlen($var) > 90) {
            fwrite($outputFile, "\n        ");
            $pos = 8;
        }

        $terminator = $i == $last ? ';' : ', ';
        fwrite($outputFile, $var . $terminator);
        $pos += strlen($var);
    }
    fwrite($outputFile, "\n\n");

    if ($helpers) {
        $helpers->write($outputFile);
    }
    foreach ($classFiles as $file) {
        $file->write($outputFile);
    }
    if ($module) {
        $module->write($outputFile);
    }
    if ($bootstrap) {
        $bootstrap->write($outputFile);
    }

    fwrite($outputFile, "}());\n");
