# phalcon-model-generator

Generates decorated Phalcon\Model files from a database

### Install

```bash
composer require lajosbencz/phalcon-model-generator
```

### Use from terminal

Parameters are options, first is path to config, second is config key of generator.

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
        'directory' => __DIR__,
        'namespace' => 'TestModels',
        'namespace_auto' => 'TestModels\Auto',
        'base_model' => \Phalcon\Mvc\Model::class,
        'base_view' => \Phalcon\Mvc\Model::class,
        'reusable' => false,
        'log' => true,
        // ignored tables (as they appear in database)
        'blacklist' => [],
    ],
]);

$g = new PhalconModelGenerator\Generator($config, 'model_generator');
$g->generate();

```

