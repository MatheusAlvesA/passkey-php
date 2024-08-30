<?php

namespace Matheus\PasskeyPhp\Controller;

use Matheus\PasskeyPhp\Service\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MainAppController
{
    public function __construct(protected $authService = new AuthService())
    {
    }

    public function login(Request $request, Response $response)
    {
        $response->getBody()->write("Hello world!");
        return $response;
    }

    public function home(Request $request, Response $response)
    {
        if(!$this->authService->login('a', 'b')) {
            $response->getBody()->write("Acesso negado");
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
