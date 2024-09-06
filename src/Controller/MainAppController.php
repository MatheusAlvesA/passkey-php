<?php

namespace Matheus\PasskeyPhp\Controller;

use Matheus\PasskeyPhp\Exception\UserActionException;
use Matheus\PasskeyPhp\Model\User;
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
        if($request->getMethod() == 'GET') {
            return $view->render($response, 'login.html.twig', []);
        }

        $data = $request->getParsedBody();
        if(empty($data['user']) || empty($data['pass'])) {
            return $view->render($response, 'login.html.twig', [
                'error' => 'Usuário e senha são obrigatórios'
            ]);
        }

        $user = $this->authService->login($data['user'], $data['pass']);
        if(empty($user)) {
            return $view->render($response, 'login.html.twig', [
                'error' => 'Usuário ou senha incorretos'
            ]);
        }

        return $response->withStatus(302)->withAddedHeader('Location', '/home');
    }

    public function updateColor(Request $request, Response $response, UserRepository $repo)
    {
        $user = $this->authService->getAuthUser();
        $newColor = $request->getParsedBody()['color'] ?? '';
        $newColor = strtoupper($newColor);

        if(
            empty($user) ||
            empty($newColor) ||
            !preg_match('#\#[A-F0-9]{6,6}#', $newColor)
        ) {
            return $response->withStatus(302)->withAddedHeader('Location', '/home');
        }
        $user->color = $newColor;
        $repo->update($user);

        return $response->withStatus(302)->withAddedHeader('Location', '/home');
    }

    public function logout(Request $request, Response $response)
    {
        $this->authService->logout();
        return $response->withStatus(302)->withAddedHeader('Location', '/');
    }

    public function home(Request $request, Response $response, Twig $view)
    {
        return $view->render($response, 'home.html.twig', [
            'user' => $this->authService->getAuthUser()
        ]);
    }

    public function singup(
        Request $request,
        Response $response,
        Twig $view,
        UserRepository $repo
    ) {
        if($request->getMethod() == 'GET') {
            return $view->render($response, 'singup.html.twig', []);
        }

        $data = $request->getParsedBody();
        if(empty($data['user']) || empty($data['pass'])) {
            return $view->render($response, 'singup.html.twig', [
                'error' => 'Usuário e senha são obrigatórios'
            ]);
        }
        $newUser = new User();
        $newUser->username = $data['user'];
        $newUser->password = $data['pass'];
        $newUser->color = '#FF0000';
        try {
            $repo->save($newUser);
        } catch(UserActionException $ex) {
            return $view->render($response, 'singup.html.twig', [
                'error' => $ex->getMessage()
            ]);
        } catch(UserActionException $ex) {
            return $view->render($response, 'singup.html.twig', [
                'error' => 'Erro desconhecido ao salvar'
            ]);
        }

        $this->authService->login($newUser->username, $newUser->password);

        return $response->withStatus(302)->withAddedHeader('Location', '/home');
    }
}
