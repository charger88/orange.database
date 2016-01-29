<?php

namespace Orange\Database\Queries\Table;

/**
 * Class Drop
 * @package Orange\Database
 * @author Mikhail Kelner
 */
class Drop extends \Orange\Database\Queries\Query
{

    /**
     * @var bool
     */
    protected $if_exists_only = false;

    /**
     * @return string
     */
    public function build()
    {
        return 'DROP TABLE ' . ($this->if_exists_only ? 'IF EXISTS ' : '') . $this->table;
    }

    /**
     * @param bool|true $if_exists_only
     * @return $this
     */
    public function setIfExistsOnly($if_exists_only = true)
    {
        $this->if_exists_only = $if_exists_only;
        return $this;
    }

}