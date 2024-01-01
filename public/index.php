<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/db/db.php';


$app = AppFactory::create();
$app->setBasePath('/chat_app/public');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$userRoutes = require '../src/routes/users.php';
$userRoutes($app);

$groupRoutes = require '../src/routes/groups.php';
$groupRoutes($app);

$messageRoutes = require '../src/routes/messages.php';
$messageRoutes($app);


$app->run();
