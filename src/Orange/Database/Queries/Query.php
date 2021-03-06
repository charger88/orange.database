<?php

namespace Orange\Database\Queries;

/**
 * Class Query
 * @package Orange\Database
 * @author Mikhail Kelner
 */
abstract class Query
{

    /**
     * @var
     */
    protected $table;
    /**
     * @var
     */
    protected $result;

    /**
     * @var \Orange\Database\Connection
     */
    protected $connection;

    /**
     * @param $table
     * @param string $connection_name
     * @throws \Orange\Database\DBException
     */
    public function __construct($table, $connection_name = 'master')
    {
        $this->connection = \Orange\Database\Connection::get($connection_name);
        $this->table = $this->connection->driver->getTablesPrefix() . $table;
    }

    /**
     * @return mixed
     */
    abstract protected function build();

    /**
     * @return $this
     */
    public function execute()
    {
        if (!$this->connection->driver->isConnected()) {
            $this->connection->driver->connect();
        }
        $sql = $this->build();
        if ($this->connection->logfile){
            file_put_contents($this->connection->logfile,$sql."\n---\n",FILE_APPEND);
        }
        if (is_array($sql)) {
            $this->result = $this->connection->driver->query(array_shift($sql));
            foreach ($sql as $s) {
                $this->connection->driver->query($s);
            }
        } else {
            $this->result = $this->connection->driver->query($sql);
        }
        return $this;
    }

    /**
     * @return \Orange\Database\Connection
     */
    protected function getConnection()
    {
        return $this->connection;
    }

}