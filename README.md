# phalcon-model-generator

Generates decorated Phalcon\Model files from a database

### Install

```bash
composer require lajosbencz/phalcon-model-generator
```

### Use from terminal

Parameters are optional, first is path to global app config, second is root key of generator config inside the global.

```bash
vendor/bin/model-generate.php
vendor/bin/model-generate.php config.php
vendor/bin/model-generate.php config.php model_generator
```

### Use from script

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$config = new \Phalcon\Config([
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'username' => 'local',
        'password' => 'local',
        'dbname' => 'test_models',
    ],
    'model_generator' => [
        // path to namespace root (will be created)
        'directory' => __DIR__,
        // namespace extensible for models
        'namespace' => 'TestModels',
        // namespace for overwritten models
        'namespace_auto' => 'TestModels\Auto',
        // models will inherit from this base class
        'base_model' => \Phalcon\Mvc\Model::class,
        // views will inherit from this base class
        'base_view' => \Phalcon\Mvc\Model::class,
        // reusable relation parameter
        'reusable' => false,
        // logging: false for off, true for stdout, string for logging to stream
        'log' => true,
        // ignored tables (as they appear in database)
        'blacklist' => [],
    ],
]);

$g = new PhalconModelGenerator\Generator($config, 'model_generator');
$g->generate();

```

