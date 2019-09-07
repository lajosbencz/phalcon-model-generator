<?php

namespace PhalconModelGenerator;


use Phalcon\Config;
use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Di\FactoryDefault;
use Phalcon\DiInterface;
use Phalcon\Logger;
use Phalcon\Mvc\User\Component;
use Phalcon\Text;

/**
 * @property Logger\Multiple $log
 */
class Generator extends Component
{
    const DEFAULT_CONFIG_KEY = 'model_generator';

    protected $_config;
    protected $_configKey;

    public function __construct(Config $config, string $configKey=null, DiInterface $di = null)
    {
        $this->_config = $config;
        $this->_configKey = $configKey ? : self::DEFAULT_CONFIG_KEY;
        if(!$config->offsetExists('database')) {
            throw new Exception('missing config key: database');
        }
        if(!$config->offsetExists($configKey)) {
            throw new Exception('missing config key: '.$configKey);
        }
        if(!$config->{$this->_configKey}->offsetExists('directory')) {
            throw new Exception('missing config key: generator.directory');
        }
        if(!$config->{$this->_configKey}->offsetExists('namespace')) {
            throw new Exception('missing config key: generator.namespace');
        }
        if(!$config->{$this->_configKey}->offsetExists('namespace_auto')) {
            throw new Exception('missing config key: generator.namespace_auto');
        }
        if(!$config->{$this->_configKey}->offsetExists('base_model')) {
            throw new Exception('missing config key: generator.base_model');
        }
        if(!$config->{$this->_configKey}->offsetExists('base_view')) {
            throw new Exception('missing config key: generator.base_view');
        }
        if(!$config->{$this->_configKey}->offsetExists('reusable')) {
            throw new Exception('missing config key: generator.reusable');
        }
        if (!$di) {
            $di = $this->getDI();
            if (!$di) {
                $di = FactoryDefault\Cli::getDefault();
                if (!$di) {
                    $di = new FactoryDefault\Cli();
                }
                $this->setDI($di);
            }
        } else {
            $this->setDI($di);
        }
        $di->setShared('db', function () use ($config) {
            $db = new Mysql($config->database->toArray());
            return $db;
        });
        $di->setShared('log', function () {
            $logger = new Logger\Multiple();
            $logger->push(new Logger\Adapter\Stream("php://stdout"));
            return $logger;
        });
    }

    public function listTables($schema = null): array
    {
        return $this->db->listTables($schema);
    }

    public function tableExists(string $table, $schema = null): bool
    {
        return $this->db->tableExists($table, $schema);
    }

    public function listViews($schema = null): array
    {
        return $this->db->listViews($schema);
    }

    public function viewExists(string $view, $schema = null): bool
    {
        return $this->db->viewExists($view, $schema);
    }

    public function describeColumns(string $table, $schema = null): array
    {
        return $this->db->describeColumns($table, $schema);
    }

    public function describeIndexes(string $table, $schema = null): array
    {
        return $this->db->describeIndexes($table, $schema);
    }

    public function describeReferences(string $table, $schema = null): array
    {
        return $this->db->describeReferences($table, $schema);
    }

    public function generate()
    {
        $database = new Database($this, (string)$this->_config->database->dbname);
        foreach ($database->tables() as $t) {
            $cn = Text::camelize($t->getName());
            $this->log->debug('Generating auto for ' . $cn);
            $this->_sourceWrite($t);
            $childPath = self::namespaceToPath($this->_config->{$this->_configKey}->directory, $this->_config->{$this->_configKey}->namespace) . $cn . '.php';
            if (!is_file($childPath)) {
                $this->log->debug('Generating child for ' . $cn);
                $autoNS = $this->_config->{$this->_configKey}->namespace_auto;
                $childNS = $this->_config->{$this->_configKey}->namespace;
                if (strpos($autoNS, $childNS) === 0) {
                    $use = "";
                    $extNS = substr($autoNS, strlen($childNS) + 1) . "\\" . $cn;
                } else {
                    //$use = "use " . $autoNS . " as Base" . $cn . ";\n\n";
                    //$extNS = "Base" . $cn;
                    $extNS = '\\' . $autoNS . $cn;
                }
                file_put_contents($childPath,
                    "<?php\n\n" .
                    "/** {@inheritdoc} */\n" .
                    "namespace " . $childNS . ";\n\n" .
                    $use .
                    "class " . $cn . " extends " . $extNS . "\n" .
                    "{\n" .
                    "}\n"
                );
            }
        }
    }

    protected function _sourceWrite(Table $table)
    {
        $n = $table->getName();
        $name = Text::camelize($n);
        $file = self::namespaceToPath($this->_config->{$this->_configKey}->directory, $this->_config->{$this->_configKey}->namespace_auto) . $name . '.php';
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0774, true);
        }
        $ns = $this->_config->{$this->_configKey}->namespace_auto;
        $ex = $table->isView() ? $this->_config->{$this->_configKey}->base_view : $this->_config->{$this->_configKey}->base_model;
        //dump($file); return;
        $source = "<?php\n\nnamespace " . $ns . ";\n\n";
        //$source .= "use " . $ex . " as BaseModel;\n\n";
        $source .= $this->_sourceProperties($table) . "\n";
        $source .= "abstract class " . $name . " extends \\$ex\n";
        $source .= "{\n";
        $source .= $this->_sourceTable($table) . "\n";
        $source .= $this->_sourceColumnMap($table) . "\n";
        $source .= $this->_sourceInitialize($table) . "\n";
        $source .= $this->_sourceFields($table) . "\n";
        $source .= $this->_sourceFind($table) . "\n";
        $source .= "}\n";
        file_put_contents($file, $source);
    }

    public static function namespaceToPath(string $dir, string $namespace)
    {
        return $dir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
    }

    protected function _sourceProperties(Table $table)
    {
        $database = $table->getDatabase();
        $tn = $table->getName();
        $ns = $this->_config->{$this->_configKey}->namespace;
        $source = "";
        $source .= "/**\n";
        foreach ($database->listBelongsTo($tn) as $t2) {
            foreach ($t2 as $rt => $t3) {
                $rtc = Text::camelize($rt);
                foreach ($t3 as $name) {
                    $source .= " * @property \\" . $ns . "\\" . $rtc . " \$" . $name . "\n";
                }
            }
        }
        foreach ($database->listHasMany($tn) as $t2) {
            foreach ($t2 as $rt => $t3) {
                $rtc = Text::camelize($rt);
                foreach ($t3 as $name) {
                    $source .= " * @property \\" . $ns . "\\" . $rtc . "[] \$" . $name . "\n";
                }
            }
        }
        $source .= " */";
        return $source;
    }

    protected function _sourceTable(Table $table)
    {
        $source = "\tpublic function getSource() {\n";
        $source .= "\t\treturn '" . $table->getName() . "';\n";
        $source .= "\t}\n";
        return $source;
    }

    protected function _sourceColumnMap(Table $table)
    {
        $source = "\tpublic function columnMap() {\n";
        $source .= "\t\treturn [\n";
        foreach ($table->columns() as $c) {
            $n = $c->getName();
            $source .= "\t\t\t'" . $n . "' => '" . $n . "',\n";
        }
        $source .= "\t\t];\n";
        $source .= "\t}\n";
        return $source;
    }

    protected function _sourceInitialize(Table $table)
    {
        $reusable = $this->_config->{$this->_configKey}->reusable ? 'true' : 'false';
        $database = $table->getDatabase();
        $source = "";
        $source .= "\t/**\n";
        $source .= "\t * @internal Virtual constructor\n";
        $source .= "\t */\n";
        $source .= "\tpublic function initialize() {\n";
        $source .= "\t\tparent::initialize();\n";
        $tn = $table->getName();
        $ns = $this->_config->{$this->_configKey}->namespace;
        foreach ($database->listBelongsTo($tn) as $tc => $refs) {
            foreach ($refs as $rt => $cols) {
                $alias = Text::camelize($rt);
                foreach ($cols as $rc => $av) {
                    $source .= "\t\t\$this->belongsTo('" . $tc . "', \\" . $ns . "\\" . $alias . "::class, '" . $rc . "', ['alias'=>'" . $av . "', 'reusable'=>" . $reusable . "]);\n";
                }
            }
        }
        foreach ($database->listHasMany($tn) as $tc => $refs) {
            foreach ($refs as $rt => $cols) {
                $alias = Text::camelize($rt);
                foreach ($cols as $rc => $av) {
                    $source .= "\t\t\$this->hasMany('" . $tc . "', \\" . $ns . "\\" . $alias . "::class, '" . $rc . "', ['alias'=>'" . $av . "', 'reusable'=>" . $reusable . "]);\n";
                }
            }
        }
        $source .= "\t}\n";
        return $source;
    }

    protected function _sourceFields(Table $table)
    {
        $source = "";
        foreach ($table->columns() as $c) {
            $n = $c->getName();
            $name = Text::camelize($n);
            $source .= "\t/** @var " . $c->getHint() . " */\n";
            $source .= "\tprotected \$" . $n . ";\n";
            if (!$table->isView()) {
                $source .= "\t/**\n";
                $source .= "\t * @param " . $c->getHint() . " \$" . $n . "\n";
                $source .= "\t * @return \$this\n";
                $source .= "\t */\n";
                $source .= "\tpublic function set" . $name . "(\$" . $n . ") {\n";
                $source .= "\t\t\$this->" . $n . " = \$" . $n . ";\n";
                $source .= "\t\treturn \$this;\n";
                $source .= "\t}\n";
            }
            $source .= "\t/**\n";
            $source .= "\t * @return " . $c->getHint() . "\n";
            $source .= "\t */\n";
            $source .= "\tpublic function get" . $name . "() {\n";
            $source .= "\t\treturn \$this->" . $n . ";\n";
            $source .= "\t}\n";
            if ($c->getHint() == 'bool' && substr($n, 0, 3) == 'is_') {
                $source .= "\t/**\n";
                $source .= "\t * @return " . $c->getHint() . "\n";
                $source .= "\t */\n";
                $source .= "\tpublic function " . lcfirst($name) . "() {\n";
                $source .= "\t\treturn \$this->" . $n . ";\n";
                $source .= "\t}\n";
            }
            $source .= "\n";
        }
        return $source;
    }

    protected function _sourceFind(Table $table)
    {
        $hint = '\\' . $this->_config->{$this->_configKey}->namespace . '\\' . Text::camelize($table->getName());
        $source = "";
        $source .= "\t/**\n";
        $source .= "\t * @param mixed \$parameters (optional)\n";
        $source .= "\t * @return " . $hint . "[]|\Phalcon\Mvc\Model\ResultsetInterface\n";
        $source .= "\t */\n";
        $source .= "\tpublic static function find(\$parameters=null) {\n";
        $source .= "\t\treturn parent::find(\$parameters);\n";
        $source .= "\t}\n\n";
        $source .= "\t/**\n";
        $source .= "\t * @param mixed \$parameters (optional)\n";
        $source .= "\t * @return " . $hint . "\n";
        $source .= "\t */\n";
        $source .= "\tpublic static function findFirst(\$parameters=null) {\n";
        $source .= "\t\treturn parent::findFirst(\$parameters);\n";
        $source .= "\t}\n\n";
        foreach ($table->columns() as $c) {
            $n = $c->getName();
            $cn = Text::camelize($n);
            $source .= "\t/**\n";
            $source .= "\t * @param mixed \$" . $n . "\n";
            $source .= "\t * @return " . $hint . "\n";
            $source .= "\t */\n";
            $source .= "\tpublic static function findFirstBy" . $cn . "(\$" . $n . ") {\n";
            $source .= "\t\treturn parent::findFirstBy" . $cn . "(\$" . $n . ");\n";
            $source .= "\t}\n\n";
            $source .= "\t/**\n";
            $source .= "\t * @param mixed \$" . $n . "\n";
            $source .= "\t * @return " . $hint . "[]|\Phalcon\Mvc\Model\ResultsetInterface\n";
            $source .= "\t */\n";
            $source .= "\tpublic static function findBy" . $cn . "(\$" . $n . ") {\n";
            $source .= "\t\treturn parent::findBy" . $cn . "(\$" . $n . ");\n";
            $source .= "\t}\n\n";
        }
        return $source;
    }
}
