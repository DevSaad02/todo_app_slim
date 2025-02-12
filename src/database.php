<?php
use Illuminate\Database\Capsule\Manager as Capsule;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
$capsule = new Capsule();
$capsule->addConnection([
    'driver' => $_ENV['DRIVER'],
    'host' => $_ENV['DB_HOST'],
    'database' => $_ENV['DB'],
    'username' => $_ENV['DB_USERNAME'],
    'password' => $_ENV['DB_PASS'],
    'charset' => $_ENV['DB_CHARSET'],
    'collation' => $_ENV['DB_COLLATION'],
    // 'prefix' => $_ENV['DB_PREFIX'],
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();
