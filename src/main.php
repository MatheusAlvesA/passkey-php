<?php

use  Matheus\PasskeyPhp\Controller\MainAppController;
use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

function setupApp(App $app): void
{
    $controller = new MainAppController();
    $app->get('/', function (Request $req, Response $res) use ($controller) {
        return $controller->login($req, $res);
    });

    $app->get('/singup', function (Request $req, Response $res) use ($controller) {
        return $controller->singup($req, $res);
    });

    $app->get('/home', function (Request $req, Response $res) use ($controller) {
        return $controller->home($req, $res);
    });
}
