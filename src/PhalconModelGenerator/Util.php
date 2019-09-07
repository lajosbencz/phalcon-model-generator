<?php

namespace PhalconModelGenerator;


abstract class Util
{
    /**
     * @param string $namespace
     * @return string
     */
    public static function namespaceToPath($namespace)
    {
        $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src';
        return $dir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
    }

    public static function pathToNamespace($path)
    {

    }
}