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


$arguments = $argv;
$relScriptPath = array_unshift($arguments);

if(in_array('help', $arguments) || in_array('--help', $arguments) || in_array('-h', $arguments)) {
    echo '', PHP_EOL;
    echo 'Usage:', PHP_EOL;
    echo 'vendor\\bin\\model-generate [config file path (config.php)] [config key (model_generator)]', PHP_EOL;
    echo '', PHP_EOL;
}

/** @var \Phalcon\Config $config */
$config = null;
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

$configKey = null;
if(count($arguments) > 0) {
    $configKey = array_unshift($arguments);
    if(!$config->offsetExists($configKey)) {
        throw new RuntimeException('invalid config key: '.$configKey);
    }
}

$generator = new \PhalconModelGenerator\Generator($config, $configKey);
$generator->generate();
