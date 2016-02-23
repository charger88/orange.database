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
    public function addWhere($condition, $operator = Condition::L_AND)
    {
        if ($condition instanceof Condition) {
            $condition->setConnection($this->getConnection());
        }
        if (empty($this->where)){
            $this->where .= ' WHERE ';
        } else {
            if (substr(trim($this->where),strlen(trim($this->where)) - 1, 1) != '(') {
                $this->where .= ($operator == Condition::L_AND ? ' AND ' : ' OR ');
            }
        }
        $this->where .= $condition->getSQL();
        return $this;
    }

    /**
     * @param boolean $open
     * @return \Orange\Database\Queries\Query
     */
    public function addWhereBracket($open = true){
        if (empty($this->where)){
            $this->where .= ' WHERE ';
        }
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