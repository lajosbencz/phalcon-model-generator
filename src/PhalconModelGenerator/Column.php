<?php

namespace PhalconModelGenerator;

use Phalcon\Db;

class Column
{
    /** @var string */
    protected $_name;
    /** @var Table */
    protected $_table;
    /** @var Db\ColumnInterface */
    protected $_data;
    /** @var array */
    protected $_array = [];

    public function __construct(Table $table, Db\ColumnInterface $column)
    {
        $this->_table = $table;
        $this->_name = $column->getName();
        $this->_data = $column;

        $this->_array = [
            'name' => $this->_data->getName(),
            'ai' => $this->_data->isAutoIncrement(),
            'primary' => $this->_data->isPrimary(),
            'type' => $this->_data->getType(),
            'typeName' => self::_getTypeString($this->_data->getType()),
            'bind' => $this->_data->getBindType(),
            'bindName' => self::_getBindTypeString($this->_data->getBindType()),
            'null' => !$this->_data->isNotNull(),
            'default' => $this->_data->getDefault(),
            'numeric' => $this->_data->isNumeric(),
            'unsigned' => $this->_data->isUnsigned(),
        ];
    }

    protected static function _getTypeString($type)
    {
        switch ($type) {
            case Db\Column::TYPE_DECIMAL:
                return 'decimal';
            case Db\Column::TYPE_INTEGER:
                return 'integer';
            case Db\Column::TYPE_BOOLEAN:
                return 'boolean';
            case Db\Column::TYPE_CHAR:
                return 'char';
            case Db\Column::TYPE_DATE:
                return 'date';
            case Db\Column::TYPE_DATETIME:
                return 'datetime';
            case Db\Column::TYPE_FLOAT:
                return 'float';
            case Db\Column::TYPE_TEXT:
                return 'text';
            default:
            case Db\Column::TYPE_VARCHAR:
                return 'varchar';
        }
    }

    protected static function _getBindTypeString($type)
    {
        switch ($type) {
            case Db\Column::BIND_PARAM_BOOL:
                return 'bool';
            case Db\Column::BIND_PARAM_DECIMAL:
                return 'float';
            case Db\Column::BIND_PARAM_INT:
                return 'int';
            case Db\Column::BIND_PARAM_STR:
                return 'string';
            default:
            case Db\Column::BIND_PARAM_NULL:
                return 'null';
        }
    }

    public function getTable()
    {
        return $this->_table;
    }

    public function getHint()
    {
        return $this->_getTypeHint($this->_array['type']);
    }

    protected static function _getTypeHint($type)
    {
        switch ($type) {
            case Db\Column::TYPE_BOOLEAN:
                return 'bool';
            case Db\Column::TYPE_DECIMAL:
            case Db\Column::TYPE_FLOAT:
                return 'float';
            case Db\Column::TYPE_INTEGER:
                return 'int';
            default:
            case Db\Column::TYPE_CHAR:
            case Db\Column::TYPE_VARCHAR:
            case Db\Column::TYPE_TEXT:
            case Db\Column::TYPE_DATE:
            case Db\Column::TYPE_DATETIME:
                return 'string';
        }
    }

    public function __toString()
    {
        return $this->toString();
    }

    public function toString()
    {
        $s = '';
        $array = $this->toArray();
        unset($array['name']);
        $array['type'] = $array['typeName'];
        unset($array['typeName']);
        $array['bind'] = $array['bindName'];
        unset($array['bindName']);
        foreach ($array as $k => $v) {
            $s .= "\t\t" . '[' . $k . ']: ' . (is_bool($v) ? ($v ? 'Y' : 'N') : $v) . PHP_EOL;
        }
        return "\t" . $this->getName() . "\n" . $s;
    }

    public function toArray()
    {
        return $this->_array;
    }

    public function getName()
    {
        return $this->_name;
    }
}