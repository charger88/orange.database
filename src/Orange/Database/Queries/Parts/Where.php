<?php

namespace Orange\Database\Queries\Parts;

trait Where
{

    protected $where = '';

    /**
     * @param $condition
     * @param integer $logic [Condition::L_OR]
     * @return \Orange\Database\Queries\Query
     */
    public function addWhere($condition, $logic = Condition::L_OR)
    {
        if ($condition instanceof Condition) {
            $condition->setConnection($this->getConnection());
        }
        $this->where .= $this->where ? ' ' . ($logic == Condition::L_AND ? 'AND' : 'OR') . ' ' : ' WHERE ';
        $this->where .= $condition;
        return $this;
    }

    abstract protected function getConnection();

}