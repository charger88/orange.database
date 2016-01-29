<?php

namespace Orange\Database;

/**
 * Class DBException
 * @package Orange\Database
 * @author Mikhail Kelner
 */
class DBException extends \Exception
{

    /**
     * @var string
     */
    protected $query = '';

    /**
     * @param string $message
     * @param string $query
     * @param int $code
     * @param \Exception|null $previous
     */
    public function __construct($message = '', $query = '', $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->query = $query;
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

}