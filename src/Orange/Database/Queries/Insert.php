<?php

namespace Orange\Database\Queries;

/**
 * Class Insert
 * @package Orange\Database
 * @author Mikhail Kelner
 */
class Insert extends Query
{

    use Parts\Field;

    /**
     * @var string
     */
    protected $fields = '';
    /**
     * @var string
     */
    protected $values = '';

    /**
     * @return string
     */
    public function build()
    {
        if ($fields = $this->fields) {
            $fields = ' (' . $this->fields . ')';
        }
        if ($values = $this->values) {
            $values = ' VALUES(' . $this->values . ')';
        }
        return 'INSERT INTO ' . $this->table . $fields . $values;
    }

    /**
     * @param $fields
     * @return $this
     * @throws \Orange\Database\DBException
     */
    public function setDataSet($fields)
    {
        $this->fields = '';
        $this->values = '';
        foreach ($fields as $field => $value) {
            $this->addData($field, $value);
        }
        return $this;
    }

    /**
     * @param $values
     * @return $this
     * @throws \Orange\Database\DBException
     */
    public function setValues($values)
    {
        foreach ($values as $value) {
            $this->addData(null, $value);
        }
        return $this;
    }

    /**
     * @param $field
     * @param $value
     * @return $this
     * @throws \Orange\Database\DBException
     */
    public function addData($field, $value)
    {
        if (!is_null($field)) {
            if (!$this->connection->driver->checkField($field)) {
                throw new \Orange\Database\DBException('Field "' . $field . '" is not correct.');
            }
            if ($this->fields) {
                $this->fields .= ', ';
            }
            $this->fields .= $field;
        }
        if ($this->values) {
            $this->values .= ', ';
        }
        $this->values .= $this->connection->driver->escape($value);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLastID()
    {
        return $this->connection->driver->getLastID();
    }

    /**
     * @return mixed
     */
    public function getAffectedRows()
    {
        return $this->connection->driver->getAffectedRows();
    }

}