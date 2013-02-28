<?php

  spl_autoload_register(function($className) {
    $className = str_replace('\\', '/', strtolower($className));
    require __DIR__.'/'.$className.'.php';
  });

  if (function_exists('__autoload')) {
    spl_autoload_register('__autoload');
  }
