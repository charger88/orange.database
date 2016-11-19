<?php

namespace Orange\Database;

use Orange\Database\Queries\Delete;
use Orange\Database\Queries\Insert;
use Orange\Database\Queries\Parts\Condition;
use Orange\Database\Queries\Select;
use Orange\Database\Queries\Table\Create;
use Orange\Database\Queries\Table\Drop;
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
            $this->values[$field] = isset($params['default']) ? $params['default'] : $this->getDefaultAppValue($field);
        }
        if (($key === false) && is_null($value)) {
            // Do nothing
        } else if (is_null($key) && is_array($value)) {
            $this->setData($value);
        } else if (is_array($key) && is_null($value)) {
            $this->setData($key,true);
        } else if (is_int($key) && is_null($value)) {
            $row = (new Select(static::$table))
                ->addWhere(new Condition('id', '=', $key))
                ->execute()
                ->getResultNextRow();
            if ($row) {
                $this->setData($row, true);
            }
        } else if (is_string($key) && is_string($value)) {
            if (!in_array($key, static::$u_keys)) {
                throw new DBException($key . ' is not unique key of class ' . get_class($this));
            }
            $row = (new Select(static::$table))
                ->addWhere(new Condition($key, '=', $value))
                ->execute()
                ->getResultNextRow();
            if ($row) {
                $this->setData($row, true);
            }
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
            $values[$key] = self::convertToDBFormat($key, $value);
        }
        if ($this->id) {
            (new Update(static::$table))
                ->setDataSet($values)
                ->addWhere(new Condition('id', '=', $this->id))
                ->execute();
        } else {
            $this->id = $this->values['id'] = (new Insert(static::$table))
                ->setDataSet($values)
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
        $type = static::$scheme[$field]['type'];
        if ($type == 'BOOLEAN') {
            $value = intval($value) ? true : false;
        } else if (($type == 'ID') || ($type == 'BIGINT') || ($type == 'INTEGER') || ($type == 'TINYINT') || ($type == 'SMALLINT')) {
            $value = intval($value);
        } else if (($type == 'TIME') || ($type == 'DATE')) {
            $value = is_numeric($value) ? $value : strtotime($value);
        }
        if ($field == 'id') {
            $this->id = $value;
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
                debug_print_backtrace();
                throw new DBException('Parameter $data of method setData should be array.');
            }
            if ($data) {
                foreach ($this->values as $field => $value) {
                    if (array_key_exists($field, $data)) {
                        $this->set($field, $raw_data ? $this->convertToAppFormat($field, $data[$field]) : $data[$field]);
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
     * @param $field
     * @return mixed
     * @throws DBException
     */
    public function type($field)
    {
        if (!array_key_exists($field, static::$scheme)) {
            throw new DBException('ActiveRecord exception: field "' . $field . '" is not exists in class ' . get_class($this) . ' (table - ' . static::$table . ')');
        }
        return static::$scheme[$field]['type'];
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
                throw new DBException('Type of field '.static::$table.'.'.$field.' is not defined.');
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
     * @throws DBException
     */
    public static function uninstall()
    {
        $drop = new Drop(static::$table);
        $drop->execute();
    }

    /**
     * @param $key
     * @param $value
     * @return int|string
     */
    protected static function convertToDBFormat($key, $value, $type = null, $length = null)
    {
        if (is_null($type)) {
            $type = static::$scheme[$key]['type'];
        }
        if (is_null($length)){
            $length = isset(static::$scheme[$key]['length']) ? static::$scheme[$key]['length'] : null;
        }
        if ($type == 'ARRAY') {
            $value = json_encode($value);
        } else if ($type == 'LIST') {
            $value = '|' . implode('|', $value ? $value : array()) . '|';
        } else if ($type == 'DATA') {
            $value = serialize($value);
        } else if ($type == 'BOOLEAN') {
            $value = $value ? 1 : 0;
        } else if ($type == 'TIME') {
            $value = date("Y-m-d H:i:s", is_numeric($value) ? $value : strtotime($value));
        } else if ($type == 'DATE') {
            $value = date("Y-m-d", is_numeric($value) ? $value : strtotime($value));
        }
        return !is_null($length) && (strlen($value) > $length) ? substr($value, 0, $length) : $value;
    }

    /**
     * @param $key
     * @param $value
     * @return bool|mixed|string
     */
    protected static function convertToAppFormat($key, $value)
    {
        $type = static::$scheme[$key]['type'];
        if ($type == 'ARRAY') {
            $value = json_decode($value, true);
        } else if ($type == 'LIST') {
            $value = explode('|', trim($value, '|'));
        } else if ($type == 'DATA') {
            $value = unserialize($value);
        }
        return $value;
    }

    /**
     * @param $key
     * @return mixed
     */
    protected function getDefaultAppValue($key)
    {
        if (isset(static::$scheme[$key]['default'])){
            return static::$scheme[$key]['default'];
        } else {
            $type = static::$scheme[$key]['type'];
            if (($type == 'ARRAY') || ($type == 'LIST')) {
                $value = array();
            } else if ($type == 'DATA') {
                $value = null;
            } else if ($type == 'BOOLEAN') {
                $value = false;
            } else if ($type == 'TIME') {
                $value = '0000-00-00 00:00:00'; //TODO Add something about now()
            } else  if ($type == 'DATE') {
                $value = '0000-00-00'; //TODO Add something about now()
            } else if (($type == 'STRING') || ($type == 'CHAR') || ($type == 'TEXT') || ($type == 'LONGTEXT')) {
                $value = '';
            } else if (($type == 'TINYINT') || ($type == 'SMALLINT') || ($type == 'INTEGER') || ($type == 'BIGINT')) {
                $value = 0;
            } else if ($type == 'FLOAT'){
                $value = 0.0;
            } else {
                $value = null;
            }
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