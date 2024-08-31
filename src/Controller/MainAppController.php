<?php

namespace Matheus\PasskeyPhp\Controller;

use Matheus\PasskeyPhp\Repository\UserRepository;
use Matheus\PasskeyPhp\Service\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Views\Twig;

class MainAppController
{
    public function __construct(
        protected AuthService $authService,
        protected App $app
    ) {
    }

    public function login(Request $request, Response $response, Twig $view)
    {
        return $view->render($response, 'login.html.twig', []);
    }

    public function home(Request $request, Response $response)
    {
        $repo = $this->app->getContainer()->get(UserRepository::class);
        $user = $repo->getById(1);
        if(!$this->authService->login('a', 'b')) {
            $response->getBody()->write("Acesso negado");
            $response->getBody()->write(print_r($user, true));
            return $response;
        }
        $response->getBody()->write("Home secret");
        return $response;
    }

    public function singup(Request $request, Response $response)
    {
        $response->getBody()->write("Cadastre-se");
        return $response;
    }
}
