<?php

namespace Orange\Database\Queries\Parts;

class Condition
{

    const L_AND = 1;
    const L_OR = 2;

    use Field;

    /**
     * @var \Orange\Database\Connection
     */
    protected $connection;

    protected static $allowed_operators = array('=', '>', '<', '!=', '>=', '<=', '<>', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN');

    protected $field;
    protected $operator;
    protected $value;
    protected $link;

    public function __construct($field, $operator, $value, $link = false)
    {
        if (!in_array($operator, self::$allowed_operators)) {
            throw new \Orange\Database\DBException('Operator "' . $operator . '" is not allowed.');
        }
        $this->field = $field;
        $this->operator = $operator;
        $this->value = $value;
        $this->link = $link;
    }

    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function __toString()
    {
        return $this->formatField($this->field) . ' ' . $this->operator . ' ' . ($this->link ? $this->formatField($this->value) : $this->formatValue($this->value));
    }

}