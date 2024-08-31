<?php

use Matheus\PasskeyPhp\Controller\MainAppController;
use Matheus\PasskeyPhp\Service\AuthService;
use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

function setupApp(App $app): void
{
    setupServices($app);
    setupView($app);

    $controller = $app->getContainer()->get(MainAppController::class);
    $app->get('/', [MainAppController::class, 'login']);

    $app->get('/singup', [MainAppController::class, 'singup']);

    $app->get('/home', [MainAppController::class, 'home']);
}

function setupServices(App $app)
{
    $app->getContainer()->set(AuthService::class, function () {
        return new AuthService();
    });
}

function setupView(App $app)
{
    $container = $app->getContainer();

    // Set view in Container
    $container->set(Twig::class, function () {
        return Twig::create(__DIR__ . '/views/templates', ['cache' => false]);
    });

    // Add Twig-View Middleware
    $app->add(TwigMiddleware::create($app, $container->get(Twig::class)));

    // Add other middleware
    $app->addRoutingMiddleware();
    $app->addErrorMiddleware(true, true, true);
}
