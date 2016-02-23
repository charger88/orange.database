<?php

namespace Orange\Database\Queries\Parts;

trait Field
{

    protected function formatField($field)
    {
        return $this->format($field, true);
    }

    protected function formatValue($value)
    {
        return $this->format($value, false);
    }

    private function format($field, $is_field)
    {
        if (is_array($field)) {
            if (!$is_field) {
                $fieldString = '';
                foreach ($field as $f) {
                    if ($fieldString){
                        $fieldString .= ',';
                    }
                    $fieldString .= $this->format($f,false);
                }
                $field = '('.$fieldString.')';
            } else {
                $function = array_shift($field);
                if (!$this->getConnection()->driver->checkFunction($function)) {
                    throw new \Orange\Database\DBException('Function "' . $function . '" is not correct.');
                }
                if ($field) {
                    foreach ($field as $i => $f) {
                        $field[$i] = $this->format($f, $is_field);
                    }
                }
                $field = $function . '(' . implode(',', $field) . ')';
            }
        } else {
            if (!$is_field) {
                $field = $this->getConnection()->driver->escape($field);
            } else {
                $parts = array();
                if (strpos($field, '.') !== false) {
                    $parts = explode('.', $field);
                    $field = array_pop($parts);
                    if ($parts) {
                        foreach ($parts as $part) {
                            if (!$this->getConnection()->driver->checkTable($part)) {
                                throw new \Orange\Database\DBException('Table name "' . $part . '" is not correct.');
                            }
                        }
                    }
                }
                if (!$this->getConnection()->driver->checkField($field)) {
                    throw new \Orange\Database\DBException('Field "' . $field . '" is not correct.');
                }
                $field = ($parts ? implode('.', $parts) . '.' : '') . $field;
            }
        }
        return $field;
    }

    abstract protected function getConnection();

}