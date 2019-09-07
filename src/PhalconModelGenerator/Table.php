<?php

namespace PhalconModelGenerator;

use Phalcon\Db;

class Table
{
    protected $_database;

    /** @var string */
    protected $_name;
    /** @var Column[] */
    protected $_columns = [];
    /** @var Db\ReferenceInterface[] */
    protected $_references = [];

    public function __construct(Database $database, string $name)
    {
        $database->getGenerator()->log->debug('Describing table: ' . $name);

        $this->_database = $database;
        $this->_name = $name;

        foreach ($database->getGenerator()->describeColumns($this->getName()) as $desc) {
            /** @var Db\ColumnInterface $desc */
            $this->_columns[$desc->getName()] = new Column($this, $desc);
        }
//        foreach ($database->getGenerator()->describeIndexes($this->getName()) as $idx) {
//            /** @var Db\IndexInterface $idx */
//            dump($idx->getType(), $idx->getColumns());
//        }
        foreach ($database->getGenerator()->describeReferences($this->getName()) as $ref) {
            /** @var Db\ReferenceInterface $ref */
            $this->_references[$ref->getName()] = $ref;
        }
    }

    public function getName()
    {
        return $this->_name;
    }

    public function isView()
    {
        return $this->getDatabase()->getGenerator()->viewExists($this->getName());
    }

    public function getDatabase()
    {
        return $this->_database;
    }

    public function columns()
    {
        return $this->_columns;
    }

    public function column($name)
    {
        return $this->_columns[$name];
    }

    public function references()
    {
        return $this->_references;
    }

    public function reference($name)
    {
        return $this->_references[$name];
    }

    public function columnReferences($column)
    {
        $refs = [];
        foreach ($this->_references as $ref) {
            if (in_array($column, $ref->getColumns())) {
                $refs[] = $ref;
            }
        }
        return $refs;
    }
}
