<?php

namespace PhalconModelGenerator;

use Phalcon\Text;

class Database
{
    protected $generator;
    protected $name;

    /** @var Table[] */
    protected $_tables = [];
    protected $_references = [];
    protected $_belongsTo = [];
    protected $_hasMany = [];
    protected $_hasManyToMany = [];

    public function __construct(Generator $generator, string $name)
    {
        $this->generator = $generator;
        $this->name = $name;
        $this->initialize();
    }

    public function initialize()
    {
        $config = $this->getGenerator()->getConfig();
        $blacklist = [];
        if($config->offsetExists('blacklist')) {
            $blacklist = (array)$config->blacklist;
        }
        foreach ($this->generator->listTables() as $table) {
            if(in_array($table, $blacklist)) {
                continue;
            }
            $this->_tables[$table] = new Table($this, $table);
        }

        foreach ($this->tables() as $t) {
            foreach ($t->references() as $r) {
                $tc = $r->getColumns();
                $rc = $r->getReferencedColumns();
                $tc = $tc[0];
                $rc = $rc[0];
                $this->_references[$t->getName()][$tc][$r->getReferencedTable()][$rc] = true;
            }
        }

        foreach ($this->tables() as $t) {
            $tn = $t->getName();
            if (isset($this->_references[$tn])) {
                foreach ($this->_references[$tn] as $tc => $refs) {
                    $tcn = preg_replace('/_id$/', '', $tc);
                    if(!array_key_exists($tcn, $this->_tables)) {
                        continue;
                    }
                    $an = Text::camelize($tcn);
                    foreach ($refs as $rt => $refCols) {
                        foreach ($refCols as $rc => $true) {
                            $this->_belongsTo[$tn][$tc][$rt][$rc] = $an;
                        }
                    }
                }
            }
            foreach ($this->_references as $rt => $refCols) {
                foreach ($refCols as $rc => $refs) {
                    $rcn = preg_replace('/_id$/', '', $rc);
                    if(!array_key_exists($rcn, $this->_tables)) {
                        continue;
                    }
                    $an = Text::camelize($rcn);
                    if (isset($refs[$tn])) {
                        foreach ($refs[$tn] as $tc => $true) {
                            $this->_hasMany[$tn][$tc][$rt][$rc] = $an;
                        }
                    }
                }
            }
        }

        foreach ($this->_hasMany as $tn => $cols) {
            foreach ($cols as $tc => $refs) {
                foreach ($refs as $rt => $refCols) {
                    $alias = Text::camelize($rt);
                    if (count($refCols) > 1) {
                        foreach ($refCols as $rc => $av) {
                            $this->_hasMany[$tn][$tc][$rt][$rc] = $alias . $av;
                        }
                    } else {
                        foreach ($refCols as $rc => $av) {
                            $this->_hasMany[$tn][$tc][$rt][$rc] = $alias;
                        }
                    }
                }
            }
        }

    }

    public function tables()
    {
        return $this->_tables;
    }

    public function getGenerator(): Generator
    {
        return $this->generator;
    }

    public function getName()
    {
        return $this->name;
    }

    public function listBelongsTo(string $table): array
    {
        if (isset($this->_belongsTo[$table])) {
            return $this->_belongsTo[$table];
        }
        return [];
    }

    public function listHasMany(string $table): array
    {
        if (isset($this->_hasMany[$table])) {
            return $this->_hasMany[$table];
        }
        return [];
    }

    public function column($table, $column)
    {
        return $this->table($table)->column($column);
    }

    public function table($table)
    {
        return $this->_tables[$table];
    }

}