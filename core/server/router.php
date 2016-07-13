<?php

/*!
 * Router for the PHP Server
 *
 * Copyright (c) 2016 Dave Olsen
 * Licensed under the MIT license
 *
 */

use \PatternLab\Config;

// set-up the project base directory
$baseDir = __DIR__."/../../";

// auto-load classes
if (file_exists($baseDir."vendor/autoload.php")) {
  require($baseDir."vendor/autoload.php");
} else {
  print "it doesn't appear that pattern lab has been set-up yet...\n";
  print "please install pattern lab's dependencies by typing: php core/bin/composer.phar install...\n";
  exit;
}

// load the options and be quiet about it
Config::init($baseDir, false);

if (($_SERVER["SCRIPT_NAME"] == "") || ($_SERVER["SCRIPT_NAME"] == "/")) {
  
  require("index.html");
  
} else if (file_exists(Config::getOption("publicDir").$_SERVER["SCRIPT_NAME"])) {
  
  return false;
  
} else {
  
  header("HTTP/1.0 404 Not Found");
  print "file doesn't exist.";
  
}
