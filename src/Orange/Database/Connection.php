<?php

namespace Orange\Database;

/**
 * Class Connection
 * @package Orange\Database
 * @author Mikhail Kelner
 */
class Connection
{

    /**
     * @var array
     */
    protected static $connections = array();

    /**
     * @var \Orange\Database\Drivers\Driver
     */
    public $driver;

    /**
     * @var string
     */
    public $logfile = '';

    /**
     * @param $config
     * @param string $name
     * @throws DBException
     */
    public function __construct($config, $name = 'master')
    {
        if (!is_array($config) || !isset($config['driver'])) {
            throw new \Orange\Database\DBException('Connection config is incorrect');
        }
        $driverclass = 'Orange\\Database\\Drivers\\' . $config['driver'];
        $this->driver = new $driverclass($config);
        self::$connections[$name] = $this;
    }

    /**
     * @param string $name
     * @return Connection
     * @throws \Orange\Database\DBException
     */
    public static function get($name = 'master')
    {
        if (!isset(self::$connections[$name])) {
            throw new \Orange\Database\DBException('Unknown connection: "' . $name . '"');
        }
        return self::$connections[$name];
    }

}