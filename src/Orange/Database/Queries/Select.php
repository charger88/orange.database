<?php

namespace Orange\Database\Queries;

use Orange\Database\DBException;

/**
 * Class Select
 * @package Orange\Database
 * @author Mikhail Kelner
 */
class Select extends Query
{

    use Parts\Where;
    use Parts\Field;

    /**
     *
     */
    const SORT_DESC = 1;
    /**
     *
     */
    const SORT_ASC = 2;

    /**
     *
     */
    const TABLE_CROSS_JOIN = 1;
    /**
     *
     */
    const TABLE_INNER_JOIN = 2;
    /**
     *
     */
    const TABLE_OUTER_JOIN = 3;
    /**
     *
     */
    const TABLE_LEFT_OUTER_JOIN = 4;
    /**
     *
     */
    const TABLE_RIGHT_OUTER_JOIN = 5;
    /**
     *
     */
    const TABLE_FULL_JOIN = 6;

    /**
     * @var string
     */
    protected $fields = '*';
    /**
     * @var string
     */
    protected $groupby = '';
    /**
     * @var string
     */
    protected $having = '';
    /**
     * @var string
     */
    protected $sort = '';
    /**
     * @var string
     */
    protected $limit = '';
    /**
     * @var string
     */
    protected $offset = '';

    /**
     * @return string
     */
    public function build()
    {
        return 'SELECT ' . $this->fields . ' FROM ' . $this->table . $this->where . $this->groupby . $this->having . $this->sort . $this->limit . $this->offset;
    }

    /**
     * @param $table
     * @param int $mode
     * @param \Orange\Database\Queries\Parts\Condition|null $condition
     * @return $this
     * @throws DBException
     */
    public function addTable($table, $mode = self::TABLE_CROSS_JOIN, $condition = null)
    {
        if ($mode == self::TABLE_CROSS_JOIN) {
            $modeSQL = 'CROSS JOIN';
        } else if ($mode == self::TABLE_INNER_JOIN) {
            $modeSQL = 'INNER JOIN';
        } else if ($mode == self::TABLE_OUTER_JOIN) {
            $modeSQL = 'OUTER JOIN';
        } else if ($mode == self::TABLE_LEFT_OUTER_JOIN) {
            $modeSQL = 'LEFT OUTER JOIN';
        } else if ($mode == self::TABLE_RIGHT_OUTER_JOIN) {
            $modeSQL = 'RIGHT OUTER JOIN';
        } else if ($mode == self::TABLE_FULL_JOIN) {
            $modeSQL = 'FULL JOIN';
            if (!is_null($condition)) {
                throw new DBException('Error! Condition should be null for FULL JOIN.');
            }
        } else {
            throw new DBException('Unknown join mode: ' . $mode . '.');
        }
        if (!$this->getConnection()->driver->checkTable($table)) {
            throw new \Orange\Database\DBException('Table name "' . $table . '" is not correct.');
        }
        if ($condition) {
            $condition->setConnection($this->getConnection());
        }
        if ($sql = trim($modeSQL . ' ' . $table . ($condition ? ' ON ' . $condition->getSQL() : ''))) {
            $this->table .= ' ' . $sql;
        }
        return $this;
    }

    /**
     * @param $field
     * @param null $as
     * @return $this
     */
    public function addField($field, $as = null)
    {
        $field = $this->formatField($field);
        if ($this->fields == '*') {
            $this->fields = '';
        } else {
            $this->fields .= ', ';
        }
        $this->fields .= $field;
        if (!is_null($as)) {
            $this->fields .= ' as ' . $as;
        }
        return $this;
    }

    /**
     * @param $field
     * @return $this
     */
    public function setGroupBy($field)
    {
        $this->groupby = ' GROUP BY ' . $this->formatField($field);
        return $this;
    }

    /**
     * @param $condition
     * @param integer $logic [Condition::L_OR]
     * @return Query
     */
    public function addHaving($condition, $logic = Parts\Condition::L_OR)
    {
        if ($condition instanceof Parts\Condition) {
            $condition->setConnection($this->connection);
        }

        if (empty($this->having)){
            $this->having .= ' HAVING ';
        } else {
            if (substr(trim($this->having),strlen(trim($this->having)) - 1) != '(') {
                $this->having .= ($operator == Parts\Condition::L_AND ? ' AND ' : ' OR ');
            }
        }
        $this->having .= $condition->getSQL();
        return $this;
    }

    /**
     * @param boolean $open
     * @return \Orange\Database\Queries\Query
     */
    public function addHavingBracket($open = true){
        if (empty($this->having)){
            $this->having .= ' HAVING ';
        }
        $this->having .= $open ? ' (' : ') ';
        return $this;
    }

    /**
     * @param integer $operator
     * @return \Orange\Database\Queries\Query
     */
    public function addHavingOperator($operator){
        $this->having .= $operator == Parts\Condition::L_AND ? ' AND ' : ' OR ';
        return $this;
    }

    /**
     * @param $field
     * @param int $sort
     * @return $this|Select
     */
    public function setOrder($field, $sort = self::SORT_ASC)
    {
        $this->sort = '';
        return $this->addOrder($field, $sort);
    }

    /**
     * @param $field
     * @param int $sort
     * @return $this
     */
    public function addOrder($field, $sort = self::SORT_ASC)
    {
        $this->sort .= $this->sort ? ', ' : ' ORDER BY ';
        $this->sort .= $this->formatField($field);
        $this->sort .= $sort == self::SORT_ASC ? ' ASC' : ' DESC';
        return $this;
    }

    /**
     * @param $limit
     * @return $this
     */
    public function setLimit($limit)
    {
        $this->limit = ' LIMIT ' . intval($limit);
        return $this;
    }

    /**
     * @param $offset
     * @return $this
     */
    public function setOffset($offset)
    {
        $this->offset = ' OFFSET ' . intval($offset);
        return $this;
    }

    /**
     * @param null $key
     * @param null|string|array $classname
     * @param bool|false $indexed
     * @return array
     * @throws DBException
     */
    public function getResultArray($key = null, $classname = null, $indexed = false)
    {
        $result = [];
        while ($row = $this->getResultNextRow($indexed)) {
            if (is_null($classname)) {
                $res = $row;
            } else if (is_array($classname)) {
                if (!is_numeric(key($classname))) {
                    $res = current($classname);
                }
            } else {
                $res = new $classname($row);
            }
            if (!is_null($key)) {
                if (!array_key_exists($key, $row)) {
                    throw new \Orange\Database\DBException('Key field is not exists');
                }
                $result[$row[$key]] = $res;
            } else {
                $result[] = $res;
            }
        }
        return $result;
    }

    /**
     * @param string $key_column
     * @param string $value_column
     * @return array
     * @throws DBException
     */
    public function getResultColumn($key_column,$value_column)
    {
        $result = [];
        while ($row = $this->getResultNextRow()) {
            if (!array_key_exists($key_column, $row)) {
                throw new \Orange\Database\DBException('Key field is not exists');
            }
            if (!array_key_exists($value_column, $row)) {
                throw new \Orange\Database\DBException('Value field is not exists');
            }
            $result[$row[$key_column]] = $row[$value_column];
        }
        return $result;
    }

    /**
     * @param string $value_column
     * @return array
     * @throws DBException
     */
    public function getResultList($value_column)
    {
        $result = [];
        while ($row = $this->getResultNextRow()) {
            if (!array_key_exists($value_column, $row)) {
                throw new \Orange\Database\DBException('Value field is not exists');
            }
            $result[] = $row[$value_column];
        }
        return $result;
    }

    /**
     * @param bool|false $indexed
     * @return mixed
     * @throws DBException
     */
    public function getResultNextRow($indexed = false)
    {
        if (is_null($this->result)) {
            throw new \Orange\Database\DBException('Query was not executed');
        }
        return $indexed
            ? $this->connection->driver->fetchRow($this->result)
            : $this->connection->driver->fetchAssoc($this->result);
    }

    /**
     * @return integer
     * @throws DBException
     */
    public function getResultNumRow()
    {
        if (is_null($this->result)) {
            throw new \Orange\Database\DBException('Query was not executed');
        }
        return $this->connection->driver->getSelectedRows($this->result);
    }

    /**
     * @return mixed
     * @throws DBException
     */
    public function getResultValue()
    {
        $res = $this->getResultNextRow();
        return $res ? array_shift($res) : null;
    }

}