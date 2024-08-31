<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/main.php';

$app = \DI\Bridge\Slim\Bridge::create();

setupApp($app);

$app->run();
