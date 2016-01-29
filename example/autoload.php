<?php

function __autoload($classname)
{

    if (is_file($filename = __DIR__ . '/../src/' . str_replace('\\', '/', $classname . '.php'))) {
        require_once $filename;
    } else {
        throw new Orange\Database\DBException('Class "' . $classname . '" is not found in file "' . $filename . '"');
    }

}