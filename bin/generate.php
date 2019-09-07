<?php

$loaded = false;
$autoload = __DIR__ . '/../vendor/autoload.php';
if(is_readable($autoload)) {
    $autoload = realpath($autoload);
    require_once $autoload;
    $loaded = true;
} else {
    $autoload = __DIR__ . '/../../../autoload.php';
    if(is_readable($autoload)) {
        $autoload = realpath($autoload);
        require_once $autoload;
        $loaded = true;
    }
}

if(!$loaded) {
    throw new RuntimeException('failed to load vendor autoload');
}

$config = null;

$arguments = $argv;
$relScriptPath = array_unshift($arguments);
if(count($arguments)>0) {
    $cfgPath = array_unshift($arguments);
    if(is_readable($cfgPath)) {
        $cfgPath = realpath($cfgPath);
        $config = require_once $cfgPath;
    }
}

if(!$config && is_readable('config.php')) {
    $config = require_once 'config.php';
}

if(!$config) {
    throw new RuntimeException('config file not found');
}

$generator = new \PhalconModelGenerator\Generator($config);
$generator->generate();
