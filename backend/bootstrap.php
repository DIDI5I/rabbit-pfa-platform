<?php

use App\Core\App;
use App\Core\Container;
use App\Core\Database;

session_start();

$config = require base_path('config.php');

$container = new Container();

$container->bind(Database::class, function () use ($config) {

    $dsn = "mysql:" . http_build_query($config['database'], '', ';');

    static $db = null;

    if ($db === null) {
        $db = new Database(
            $dsn,
            $config['username'],
            $config['password']
        );
    }

    return $db;
});

App::bind($container);