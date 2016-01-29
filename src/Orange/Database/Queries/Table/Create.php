<?php

namespace Orange\Database\Queries\Table;

/**
 * Class Create
 * @package Orange\Database
 * @author Mikhail Kelner
 */
class Create extends \Orange\Database\Queries\Query
{

    use \Orange\Database\Queries\Parts\Field;

    /**
     * @var array
     */
    protected $fields = [];
    /**
     * @var array
     */
    protected $keys = [];
    /**
     * @var array
     */
    protected $u_keys = [];
    /**
     * @var string
     */
    protected $charset = '';
    /**
     * @var array
     */
    protected $options = [];
    /**
     * @var bool
     */
    protected $if_not_exists_only = false;

    /**
     * @return mixed
     */
    public function build()
    {
        return $this->getConnection()->driver->createTableSQLStatement($this->table, $this->fields, $this->u_keys, $this->keys, $this->options, $this->if_not_exists_only);
    }

    /**
     * @param $options
     * @return $this
     */
    public function setAdditionalOptions($options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @param $field
     * @param $type
     * @param null $length
     * @param null $default
     * @param bool|false $null
     * @return $this
     */
    public function addField($field, $type, $length = null, $default = null, $null = false)
    {
        $this->fields[] = $this->getConnection()->driver->getTypeForActiveRecord($this->formatField($field), $type, $length, $this->formatValue($default), $null);
        return $this;
    }

    /**
     * @param bool|true $if_not_exists_only
     * @return $this
     */
    public function setIfNotExistsOnly($if_not_exists_only = true)
    {
        $this->if_not_exists_only = $if_not_exists_only;
        return $this;
    }

    /**
     * @param $fields
     * @param bool|false $is_unique
     * @return $this
     */
    public function addKey($fields, $is_unique = false)
    {
        if ($is_unique) {
            $this->u_keys[] = $fields;
        } else {
            $this->keys[] = $fields;
        }
        return $this;
    }

}