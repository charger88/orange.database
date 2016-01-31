<?php

namespace Orange\Database;

use Orange\Database\Queries\Delete;
use Orange\Database\Queries\Insert;
use Orange\Database\Queries\Parts\Condition;
use Orange\Database\Queries\Select;
use Orange\Database\Queries\Table\Create;
use Orange\Database\Queries\Update;

/**
 * Class ActiveRecord
 * @package Orange\Database
 * @author Mikhail Kelner
 */
abstract class ActiveRecord
{

    /**
     * @var int
     */
    public $id = 0;

    /**
     * @var
     */
    protected static $table;
    /**
     * @var array
     */
    protected static $scheme = [];
    /**
     * @var array
     */
    protected static $u_keys = [];
    /**
     * @var array
     */
    protected static $keys = [];
    /**
     * @var array
     */
    protected $values = [];

    /**
     * @param null $key
     * @param null $value
     * @throws DBException
     */
    public function __construct($key = null, $value = null)
    {
        if (!static::$table) {
            throw new DBException('Table is not defined for class ' . get_class($this));
        }
        if (!static::$scheme) {
            throw new DBException('Scheme is not defined for class ' . get_class($this));
        }
        foreach (static::$scheme as $field => $params) {
            $this->values[$field] = isset($params['default']) ? $params['default'] : null;
        }
        if (is_null($key) && is_array($value)) {
            $this->setData($value);
        } else if (is_int($key) && is_null($value)) {
            $this->setData((new Select(static::$table))
                ->addWhere(new Condition('id', '=', $key))
                ->execute()
                ->getResultNextRow()
                , true);
        } else if (is_string($key) && is_string($value)) {
            if (!in_array($key, static::$u_keys)) {
                throw new DBException($key . ' is not unique key of class ' . get_class($this));
            }
            $this->setData((new Select(static::$table))
                ->addWhere(new Condition($key, '=', $value))
                ->execute()
                ->getResultNextRow()
                , true);
        } else if (is_array($key) && is_array($value)) {
            if (!in_array($key, static::$u_keys)) {
                throw new DBException('[' . implode(' ,', $key) . '] is not unique key of class ' . get_class($this));
            }
            $select = new Select(static::$table);
            $values = array_combine($key, $value);
            foreach ($values as $k => $v) {
                $select->addWhere(new Condition($k, '=', $v), Condition::L_AND);
            }
            $this->setData($select->execute()->getResultNextRow(), true);
        } else {
            if (!is_null($key) || !is_null($value)) {
                throw new DBException('Incorrect parameters types of ActiveRecord constructor (' . gettype($key) . ', ' . gettype($value) . ')');
            }
        }
    }

    /**
     * @return $this
     */
    public function save()
    {
        $values = [];
        foreach ($this->values as $key => $value) {
            $values[$key] = $this->convertToDBFormat($key, $value);
        }
        if ($this->id) {
            (new Update(static::$table))
                ->setDataSet($this->values)
                ->addWhere(new Condition('id', '=', $this->id))
                ->execute();
        } else {
            $this->id = $this->values['id'] = (new Insert(static::$table))
                ->setDataSet($this->values)
                ->execute()
                ->getLastID();
        }
        return $this;
    }

    /**
     * @return $this
     * @throws DBException
     */
    public function delete()
    {
        if (!$this->id) {
            throw new DBException('Attempt to delete not existed object.');
        }
        $delete = new Delete(static::$table);
        $delete
            ->addWhere(new Condition('id', '=', $this->id))
            ->execute();
        $this->id = $this->values['id'] = 0;
        return $this;
    }

    /**
     * @param $field
     * @param $value
     * @return $this
     * @throws DBException
     */
    public function set($field, $value)
    {
        if (!array_key_exists($field, $this->values)) {
            throw new DBException('ActiveRecord exception: field "' . $field . '" is not exists in class ' . get_class($this) . ' (table - ' . static::$table . ')');
        }
        if ($field == 'id') {
            $this->id = intval($value);
        }
        $this->values[$field] = $value;
        return $this;
    }

    /**
     * @param $data
     * @param bool|false $raw_data
     * @return $this
     * @throws DBException
     */
    public function setData($data, $raw_data = false)
    {
        if (!is_null($data)) {
            if (!is_array($data)) {
                throw new DBException('Parameter $data of method setData should be array.');
            }
            if ($data) {
                foreach ($this->values as $field => $value) {
                    if (array_key_exists($field, $data)) {
                        $this->set($field, $raw_data ? $this->convertToAppFormat($field, $data[$field]) : $raw_data);
                    }
                }
            }
        }
        return $this;
    }

    /**
     * @param $field
     * @return mixed
     * @throws DBException
     */
    public function get($field)
    {
        if (!array_key_exists($field, $this->values)) {
            throw new DBException('ActiveRecord exception: field "' . $field . '" is not exists in class ' . get_class($this) . ' (table - ' . static::$table . ')');
        }
        return $this->values[$field];
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->values;
    }

    /**
     * @throws DBException
     */
    public static function install()
    {
        $create = new Create(static::$table);
        $create->setIfNotExistsOnly();
        foreach (static::$scheme as $field => $info) {
            $length = isset($info['length']) ? $info['length'] : null;
            if (!isset($info['type'])) {
                throw new DBException('Field type is not defined.');
            }
            $create->addField($field, $info['type'], $length, isset($info['default']) ? $info['default'] : null, isset($info['null']) ? true : false);
        }
        if (static::$u_keys) {
            foreach (static::$u_keys as $key) {
                $create->addKey($key, true);
            }
        }
        if (static::$keys) {
            foreach (static::$keys as $key) {
                $create->addKey($key, false);
            }
        }
        $create->execute();
    }

    /**
     * @param $key
     * @param $value
     * @return int|string
     */
    private function convertToDBFormat($key, $value)
    {
        $type = static::$scheme[$key]['type'];
        $length = isset(static::$scheme[$key]['length']) ? static::$scheme[$key]['length'] : null;
        if ($type == 'ARRAY') {
            $value = json_encode($value);
        } else if ($type == 'LIST') {
            $value = '|' . implode('|', $value) . '|';
        } else if ($type == 'BOOLEAN') {
            $value = $value ? 1 : 0;
        } else if ($type == 'TIME') {
            $value = date("Y-m-d H:i:s", is_numeric($value) ? $value : strtotime($value));
        }
        return !is_null($length) && (strlen($value) > $length) ? substr($value, 0, $length) : $value;
    }

    /**
     * @param $key
     * @param $value
     * @return bool|mixed|string
     */
    private static function convertToAppFormat($key, $value)
    {
        $type = static::$scheme[$key]['type'];
        if ($type == 'ARRAY') {
            $value = json_decode($value, true);
        } else if ($type == 'LIST') {
            $value = trim(explode('|', $value), '|');
        } else if ($type == 'BOOLEAN') {
            $value = intval($value) ? true : false;
        }
        return $value;
    }

    /**
     * @return string
     */
    public static function getTableName()
    {
        return static::$table;
    }

}