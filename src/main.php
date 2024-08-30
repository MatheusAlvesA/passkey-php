<?php

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

function setupApp(App $app): void
{
    $app->get('/', function (Request $request, Response $response, $args) {
        $response->getBody()->write("Hello world!");
        return $response;
    });

    $app->get('/singup', function (Request $request, Response $response, $args) {
        $response->getBody()->write("Cadastre-se");
        return $response;
    });

    $app->get('/home', function (Request $request, Response $response, $args) {
        $response->getBody()->write("área logada");
        return $response;
    });
}
