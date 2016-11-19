<?php

use \Orange\Database\Queries\Select;

class Article extends \Orange\Database\ActiveRecord
{

    protected static $table = 'article';

    protected static $scheme = [
        'id' => ['type' => 'ID'],
        'title' => ['type' => 'STRING', 'default' => '', 'length' => 256],
        'content' => ['type' => 'TEXT', 'default' => ''],
        'published' => ['type' => 'TIME', 'default' => '0000-00-00 00:00:00'],
        'user_id' => ['type' => 'INTEGER', 'default' => 0],
    ];

    protected static $keys = ['user_id'];

    public static function getLatestArticles($limit = 5)
    {
        return (new Select(self::$table))
            ->setOrder('id', Select::SORT_DESC)
            ->setLimit($limit)
            ->execute()
            ->getResultArray('id', __CLASS__);
    }

}

class User extends \Orange\Database\ActiveRecord
{

    protected static $table = 'users';

    protected static $scheme = [
        'id' => ['type' => 'ID'],
        'login' => ['type' => 'STRING', 'default' => '', 'length' => 32],
        'name' => ['type' => 'STRING', 'default' => '', 'length' => 128],
        'email' => ['type' => 'STRING', 'default' => '', 'length' => 128],
        'status' => ['type' => 'BOOLEAN', 'default' => 0],
    ];

    protected static $u_keys = ['login', ['email', 'status']];

}