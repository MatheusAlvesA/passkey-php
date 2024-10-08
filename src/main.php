<?php

use Doctrine\DBAL\Connection;
use Dotenv\Dotenv;
use Matheus\PasskeyPhp\Controller\MainAppController;
use Matheus\PasskeyPhp\Repository\PublicKeyCredentialSourceRepository;
use Matheus\PasskeyPhp\Repository\UserRepository;
use Matheus\PasskeyPhp\Service\AuthService;
use Slim\App;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Doctrine\DBAL\DriverManager;

function setupApp(App $app): void
{
    Dotenv::createImmutable(__DIR__ . '/../')->load();

    setupDatabase($app);
    setupServices($app);
    setupView($app);

    $app->get('/', [MainAppController::class, 'login']);
    $app->post('/', [MainAppController::class, 'login']);
    $app->get('/singup', [MainAppController::class, 'singup']);
    $app->post('/singup', [MainAppController::class, 'singupSubmit']);
    $app->get('/home', [MainAppController::class, 'home']);
    $app->get('/logout', [MainAppController::class, 'logout']);
    $app->post('/update-color', [MainAppController::class, 'updateColor']);
    $app->get('/generate-registration-challenge', [MainAppController::class, 'generateRegistrationChallenge']);
    $app->post('/validate-registration-challenge', [MainAppController::class, 'validateRegistrationChallenge']);
    $app->get('/generate-authentication-challenge', [MainAppController::class, 'generateAuthenticationChallenge']);
    $app->post('/validate-authentication-challenge', [MainAppController::class, 'validateAuthenticationChallenge']);
}

function setupServices(App $app)
{
    $app->getContainer()->set(AuthService::class, function ($c) {
        return new AuthService(
            $c->get(UserRepository::class),
            $c->get(PublicKeyCredentialSourceRepository::class)
        );
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

function setupDatabase(App $app)
{
    $app->getContainer()->set(Connection::class, function () {
        $connectionParams = [
            'dbname' => $_ENV['db_name'],
            'user' => $_ENV['db_user'],
            'password' => $_ENV['db_pass'],
            'host' => $_ENV['db_host'],
            'driver' => 'pdo_mysql',
        ];
        return DriverManager::getConnection($connectionParams);
    });
}
