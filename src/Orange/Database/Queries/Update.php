<?php

namespace Orange\Database\Queries;

/**
 * Class Update
 * @package Orange\Database
 * @author Mikhail Kelner
 */
class Update extends Query
{

    use Parts\Where;
    use Parts\Field;

    /**
     * @var
     */
    protected $fields;

    /**
     * @return string
     */
    public function build()
    {
        return 'UPDATE ' . $this->table . ' SET ' . $this->fields . $this->where;
    }

    /**
     * @param $fields
     * @return $this
     * @throws \Orange\Database\DBException
     */
    public function setDataSet($fields)
    {
        $this->fields = '';
        foreach ($fields as $field => $value) {
            $this->addData($field, $value);
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
        if (!$this->connection->driver->checkField($field)) {
            throw new \Orange\Database\DBException('Field "' . $field . '" is not correct.');
        }
        if ($this->fields) {
            $this->fields .= ', ';
        }
        $this->fields .= $field . ' = ' . $this->connection->driver->escape($value);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAffectedRows()
    {
        return $this->connection->driver->getAffectedRows();
    }

}