<?php

header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/classes.php';

use \Orange\Database\Connection;

$config = json_decode(file_get_contents(__DIR__ . '/config-my.json'), true);

try {

    $connection = new Connection($config); // Initialize connection

    Article::install();
    User::install();

    $user = new User('login', 'admin');
    if (!$user->id) {
        $data = array(
            'login' => 'admin',
            'name' => 'Administrator',
            'email' => 'info@example.org',
            'status' => true
        );
        $user->setData($data)->save();
    }

    $article = new Article();
    $article
        ->set('title', 'My article number one')
        ->set('content', 'Text of my first article')
        ->set('user_id', $user->id)
        ->save();

} catch (\Orange\Database\DBException $e) {
    echo 'Orange database exception: ' . $e->getMessage() . (($query = $e->getQuery()) ? ". Query:\n" . $query . "\n" . $e->getTraceAsString() . "\n" : '');
}