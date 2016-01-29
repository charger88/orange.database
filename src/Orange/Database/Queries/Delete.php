<?php

namespace Orange\Database\Queries;

/**
 * Class Delete
 * @package Orange\Database
 * @author Mikhail Kelner
 */
class Delete extends Query
{

    use Parts\Where;
    use Parts\Field;

    /**
     * @return string
     */
    public function build()
    {
        return 'DELETE FROM ' . $this->table . $this->where;
    }

    /**
     * @return mixed
     */
    public function getAffectedRows()
    {
        return $this->connection->driver->getAffectedRows();
    }

}