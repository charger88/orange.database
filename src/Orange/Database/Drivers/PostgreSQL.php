<?php

namespace Orange\Database\Drivers;

use Orange\Database\DBException;

class PostgreSQL implements Driver
{

    protected $connection;
    protected $config;
    protected $result;
    protected $last_insert_table;

    protected $connected = false;

    public function __construct($config)
    {
        if (!isset($config['server'])) {
            throw new DBException('Database config error: server is not defined');
        }
        if (!isset($config['port'])) {
            throw new DBException('Database config error: port is not defined');
        }
        if (!isset($config['database'])) {
            throw new DBException('Database config error: database is not defined');
        }
        if (!isset($config['user'])) {
            throw new DBException('Database config error: user is not defined');
        }
        if (!isset($config['password'])) {
            throw new DBException('Database config error: password is not defined');
        }
        if (!isset($config['charset'])) {
            throw new DBException('Database config error: charset is not defined');
        }
        $this->config = $config;
    }

    public function connect()
    {
        $connection_string = 'host=' . $this->config['server'] . ' port=' . $this->config['port'] . ' dbname=' . $this->config['database'] . ' user=' . $this->config['user'] . ' password=' . $this->config['password'];
        if ($this->config['charset']) {
            $connection_string .= " options='--client_encoding=" . $this->config['charset'] . "'";
        }
        $this->connection = pg_connect($connection_string);
        if (!$this->connection) {
            throw new DBException('SQL connection error');
        }
        $this->connected = true;
    }

    public function isConnected()
    {
        return $this->connected;
    }

    public function closeConnection()
    {
        if ($this->isConnected()) {
            $this->connected = false;
            pg_close($this->connection);
        }
    }

    public function query($sql)
    {
        if (!$this->isConnected()) {
            $this->connect();
        }
        $this->result = pg_query($sql);
        if (strpos($sql, 'INSERT INTO ') === 0) {
            $this->last_insert_table = explode(' ', $sql)[2];
        }
        if ($this->result === false) {
            throw new DBException('SQL query error: ' . pg_last_error(), $sql);
        }
        return $this->result;
    }

    public function checkTable($table)
    {
        return $this->checkIdentifier($table);
    }

    public function checkFunction($function)
    {
        return $this->checkIdentifier($function);
    }

    public function checkField($field)
    {
        return $this->checkIdentifier($field);
    }

    protected function checkIdentifier($identifier)
    {
        return preg_match('/^([0-9a-zA-Z\\$\\_]{1,64})$/', $identifier);
    }

    public function escape($value, $field = '')
    {
        if (is_null($value)) {
            return $field === 'id' ? 'DEFAULT' : 'NULL';
        } else if ($value === true) {
            return 'TRUE';
        } else if ($value === false) {
            return 'FALSE';
        } else {
            if (!$this->isConnected()) {
                $this->connect();
            }
            if (is_object($value)) {
                throw new DBException('Incorrect value for escaping. Something goes wrong. ' . print_r($value, true));
            }
            return "'" . pg_escape_string($value) . "'";
        }
    }

    /**
     * @param \mysqli_result $result
     * @return array|null
     */
    public function fetchRow($result)
    {
        return pg_fetch_row($result);
    }

    /**
     * @param \mysqli_result $result
     * @return array|null
     */
    public function fetchAssoc($result)
    {
        return pg_fetch_assoc($result);
    }

    public function createTableSQLStatement($table, $fields, $u_keys, $keys, $options, $if_not_exists_only)
    {
        $sql = [];
        $sql[] = 'CREATE TABLE ' . ($if_not_exists_only ? 'IF NOT EXISTS ' : '') . $table . ' (' . "\n\t" . implode(",\n\t", $fields) . ');';
        if ($u_keys || $keys) {
            foreach ($u_keys as $i => $uk) {
                $sql[] = 'CREATE UNIQUE INDEX IF NOT EXISTS ' . (is_array($uk) ? implode('_', $uk) : $uk) . ' ON ' . $table . ' (' . (is_array($uk) ? implode(', ', $uk) : $uk) . ')';
            }
            foreach ($keys as $i => $uk) {
                $sql[] = 'CREATE INDEX IF NOT EXISTS ' . (is_array($uk) ? implode('_', $uk) : $uk) . ' ON ' . $table . ' (' . (is_array($uk) ? implode(', ', $uk) : $uk) . ')';
            }
        }
        return $sql;
    }

    public function getTypeForActiveRecord($field, $type, $length, $default, $null)
    {
        $ignore_charset = true;
        $ignore_null = false;
        $ignore_default = false;
        if ($type == 'STRING') {
            if (!$length) {
                throw new DBException('Length is not defined for field "' . $field . '"');
            }
            $type = 'VARCHAR(' . $length . ')';
            $ignore_charset = false;
        } else if ($type == 'CHAR') {
            if (!$length) {
                throw new DBException('Length is not defined for field "' . $field . '"');
            }
            $type = 'CHAR(' . $length . ')';
            $ignore_charset = false;
        } else if ($type == 'TIME') {
            $type = 'TIMESTAMP';
        } else if ($type == 'DATE') {
            $type = 'DATE';
        } else if ($type == 'BIGINT') {
            $type = 'BIGINT';
        } else if ($type == 'INTEGER') {
            $type = 'INTEGER';
        } else if ($type == 'SMALLINT') {
            $type = 'SMALLINT';
        } else if ($type == 'TINYINT') {
            $type = 'SMALLINT';
        } else if ($type == 'BOOLEAN') {
            $type = 'BOOLEAN';
        } else if ($type == 'FLOAT') {
            $type = 'FLOAT';
        } else if ($type == 'ID') {
            if ($null) {
                throw new DBException('ID could be NOT NULL only');
            }
            $ignore_null = true;
            $ignore_default = true;
            $type = 'BIGSERIAL PRIMARY KEY';
        } else {
            if (is_null($length)) {
                $type = 'TEXT';
            } else if ($length > 65535) {
                $type = 'TEXT';
            } else if ($length > 8192) {
                $type = 'TEXT';
            } else {
                $type = 'VARCHAR(' . $length . ')';
            }
            $ignore_charset = false;
        }
        if (!$ignore_charset) {
            // Do nothing
        }
        if (!$ignore_null && !$null) {
            $type .= ' NOT NULL';
        }
        if (!$ignore_default && !is_null($default) && ($default !== 'NULL')) {
            if ((strpos($type, 'TIMESTAMP') === 0) && ($default === '\'0000-00-00 00:00:00\'')) {
                $default = '\'0001-01-01 00:00:00\'';
            }
            $type .= ' DEFAULT ' . $default;
        }
        return $field . ' ' . $type;
    }

    public function getLastID()
    {
        $sql = 'SELECT MAX(id) FROM ' . $this->last_insert_table . ';';
        if (!($res = pg_query($sql))) {
            throw new DBException('Next ID retrieving was failed for table "' . $this->last_insert_table . '"');
        }
        return pg_fetch_row($res)[0];
    }

    public function getAffectedRows()
    {
        return pg_affected_rows($this->result);
    }

    public function getSelectedRows($result)
    {
        return pg_num_rows($result);
    }

    public function __destruct()
    {
        $this->closeConnection();
    }

    public function setTimezone($timezone)
    {
        pg_query('set timezone=\'' . $this->escape($timezone) . '\';');
    }

    public function getTablesPrefix()
    {
        return isset($this->config['prefix']) ? $this->config['prefix'] : '';
    }

}