<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Auth.php';

use Dotenv\Dotenv;

session_start(); 

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();
