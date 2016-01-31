<?php

namespace Orange\Database\Drivers;

use Orange\Database\DBException;

class MySQL implements Driver
{

    /**
     * @var \mysqli
     */
    protected $mysqli;
    protected $config;
    protected $result;

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
        $this->mysqli = @new \mysqli($this->config['server'], $this->config['user'], $this->config['password'], $this->config['database'], $this->config['port']);
        if ($this->mysqli->connect_error) {
            throw new DBException('SQL connection error (' . $this->mysqli->connect_errno . '): ' . $this->mysqli->connect_error);
        }
        if ($this->config['charset']) {
            if (!$this->mysqli->set_charset($this->config['charset'])) {
                throw new DBException('SQL charset error (' . $this->mysqli->errno . '): ' . $this->mysqli->error);
            }
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
            $this->mysqli->close();
        }
    }

    public function query($sql)
    {
        if (!$this->isConnected()) {
            $this->connect();
        }
        $result = $this->mysqli->query($sql);
        if ($result === false) {
            throw new DBException('SQL query error (' . $this->mysqli->errno . '): ' . $this->mysqli->error, $sql);
        }
        return $result;
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

    public function escape($value)
    {
        if (is_null($value)) {
            return 'NULL';
        } else {
            if (!$this->isConnected()) {
                $this->connect();
            }
            return "'" . $this->mysqli->real_escape_string($value) . "'";
        }
    }

    /**
     * @param \mysqli_result $result
     * @return array|null
     */
    public function fetchRow($result)
    {
        return $result->fetch_row();
    }

    /**
     * @param \mysqli_result $result
     * @return array|null
     */
    public function fetchAssoc($result)
    {
        return $result->fetch_assoc();
    }

    public function createTableSQLStatement($table, $fields, $u_keys, $keys, $options, $if_not_exists_only)
    {
        $sql = 'CREATE TABLE ' . ($if_not_exists_only ? 'IF NOT EXISTS ' : '') . $table . ' (' . "\n\t" . implode(",\n\t", $fields);
        if ($u_keys || $keys) {
            foreach ($u_keys as $i => $uk) {
                $sql .= "\n\t" . ', UNIQUE KEY ' . (is_array($uk) ? implode('_', $uk) : $uk) . ' (' . (is_array($uk) ? implode(', ', $uk) : $uk) . ')';
            }
            foreach ($keys as $i => $uk) {
                $sql .= "\n\t" . ', KEY ' . (is_array($uk) ? implode('_', $uk) : $uk) . ' (' . (is_array($uk) ? implode(', ', $uk) : $uk) . ')';
            }
        }
        $sql .= "\n" . ')';
        if (isset($options['engine'])) {
            $sql .= ' ENGINE=' . $options['engine'];
        }
        if (isset($this->config['charset'])) {
            $sql .= ' CHARSET=' . $this->config['charset'];
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
            $type = 'INT';
        } else if ($type == 'TINYINT') {
            $type = 'TINYINT';
        } else if ($type == 'BOOLEAN') {
            $type = 'TINYINT';
        } else if ($type == 'FLOAT') {
            $type = 'FLOAT';
        } else if ($type == 'ID') {
            if ($null) {
                throw new DBException('ID could be NOT NULL only');
            }
            $ignore_null = true;
            $ignore_default = true;
            $type = 'BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY';
        } else {
            if (is_null($length)) {
                $type = 'TEXT';
            } else if ($length > 65535) {
                $type = 'LONGTEXT';
            } else if ($length > 8192) {
                $type = 'TEXT';
            } else {
                $type = 'VARCHAR(' . $length . ')';
            }
            $ignore_charset = false;
        }
        if (!$ignore_charset) {
            if (isset($this->config['charset'])) {
                $type .= ' CHARACTER SET ' . $this->config['charset'];
            }
            if (isset($this->config['collation'])) {
                $type .= ' COLLATE ' . $this->config['collation'];
            }
        }
        if (!$ignore_null && !$null) {
            $type .= ' NOT NULL';
        }
        if (!$ignore_default && !is_null($default) && ($default !== 'NULL')) {
            $type .= ' DEFAULT ' . $default;
        }
        return $field . ' ' . $type;
    }

    public function getLastID()
    {
        return $this->mysqli->insert_id;
    }

    public function getAffectedRows()
    {
        return $this->mysqli->affected_rows;
    }

    public function getSelectedRows($result)
    {
        return $result->num_rows;;
    }

    public function __destruct()
    {
        $this->closeConnection();
    }

}