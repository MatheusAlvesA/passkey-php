<?php

use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/main.php';

$app = AppFactory::create();

setupApp($app);

$app->run();
