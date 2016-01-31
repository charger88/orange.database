<?php

namespace Orange\Database\Queries\Parts;

trait Where
{

    protected $where = '';

    /**
     * @param $condition
     * @param integer $operator [Condition::L_OR]
     * @return \Orange\Database\Queries\Query
     */
    public function addWhere($condition, $operator = Condition::L_OR)
    {
        if ($condition instanceof Condition) {
            $condition->setConnection($this->getConnection());
        }
        if ($this->where){
            if (substr($this->where,strlen($this->where) - 2) != '(') {
                $this->where .= ($operator == Condition::L_AND ? ' AND ' : ' OR ');
            }
        } else {
            $this->where .= ' WHERE ';
        }
        $this->where .= $condition;
        return $this;
    }

    /**
     * @param boolean $open
     * @return \Orange\Database\Queries\Query
     */
    public function addWhereBracket($open = true){
        $this->where .= $open ? ' (' : ') ';
        return $this;
    }

    /**
     * @param integer $operator
     * @return \Orange\Database\Queries\Query
     */
    public function addWhereOperator($operator){
        $this->where .= $operator == Condition::L_AND ? ' AND ' : ' OR ';
        return $this;
    }

    abstract protected function getConnection();

}