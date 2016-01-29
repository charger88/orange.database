<?php

namespace Orange\Database\Queries\Table;

/**
 * Class Truncate
 * @package Orange\Database
 * @author Mikhail Kelner
 */
class Truncate extends \Orange\Database\Queries\Query
{

    /**
     * @return string
     */
    public function build()
    {
        return 'TRUNCATE TABLE ' . $this->table;
    }

}