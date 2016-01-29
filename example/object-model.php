<?php

header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/classes.php';

use \Orange\Database\Connection;
use \Orange\Database\Queries\Insert;
use \Orange\Database\Queries\Update;
use \Orange\Database\Queries\Delete;
use \Orange\Database\Queries\Select;
use \Orange\Database\Queries\Table\Create;
use \Orange\Database\Queries\Table\Drop;
use \Orange\Database\Queries\Table\Truncate;
use \Orange\Database\Queries\Parts\Condition;

$config = json_decode(file_get_contents(__DIR__ . '/config.json'), true);

try {

    $connection = new Connection($config); // Initialize connection

    (new Create('orange'))// Create table 'orange'
    ->addField('id', 'ID')// Field 'id' is primary key of the table
    ->addField('title', 'STRING', 256)// Field 'title' will be VARCHAR(256)
    ->addField('status', 'BOOLEAN')// Boolean field
    ->setIfNotExistsOnly()
        ->execute(); // Execute query

    (new Insert('orange'))
        ->setDataSet(array('title' => 'My title 1', 'status' => true))// Set data
        ->execute() // Execute query
    ;

    (new Insert('orange'))
        ->setDataSet(array('title' => 'My title 2', 'status' => true))// Set data
        ->execute() // Execute query
    ;

    (new Insert('orange'))
        ->setDataSet(array('title' => 'My title 3', 'status' => false))// Set data
        ->execute() // Execute query
    ;

    (new Update('orange'))
        ->addData('title', 'Disabled record')// Set data
        ->addWhere(new Condition('status', '=', 0))// Set condition
        ->execute() // Execute query
    ;

    (new Delete('orange'))
        ->addWhere(new Condition('title', 'LIKE', 'My title 2'))// Set condition
        ->execute() // Execute query
    ;

    $select = (new Select('orange'))
        ->addWhere(new Condition('title', 'LIKE', 'My title %'))// Set condition
        ->addWhere(new Condition('status', '=', 0), Condition::L_OR)// Add condition
        ->execute() // Execute query
    ;

    while ($row = $select->getResultNextRow()) {
        print_r($row);
    }

    (new Truncate('orange'))
        ->execute();

    (new Drop('orange'))
        ->execute(); // Drop table 'orange'

} catch (\Orange\Database\DBException $e) {
    echo 'Orange database exception: ' . $e->getMessage() . (($query = $e->getQuery()) ? ". Query:\n" . $query . "\n" . $e->getTraceAsString() . "\n" : '');
}