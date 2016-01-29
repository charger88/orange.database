<?php

namespace Orange\Database\Drivers;

interface Driver
{

    public function __construct($config);

    public function connect();

    public function isConnected();

    public function closeConnection();

    public function query($sql);

    public function checkTable($value);

    public function checkFunction($value);

    public function checkField($value);

    public function escape($value);

    public function fetchRow($result);

    public function fetchAssoc($result);

    public function createTableSQLStatement($table, $fields, $u_keys, $keys, $options, $if_not_exists_only);

    public function getTypeForActiveRecord($field, $type, $length, $default, $null);

    public function getLastID();

    public function getAffectedRows();

}