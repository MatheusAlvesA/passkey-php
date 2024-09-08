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

        $user = $this->authService->login($data['user']);
        if(empty($user)) {
            return $view->render($response, 'login.html.twig', [
                'error' => 'Usuário ou senha incorretos'
            ]);
        }

        return $response->withStatus(302)->withAddedHeader('Location', '/home');
    }

    public function generateRegistrationChallenge(Request $request, Response $response)
    {
        $user = $this->authService->getAuthUser();
        if(empty($user)) {
            return $response->withStatus(302)->withAddedHeader('Location', '/');
        }

        $challenge = $this->authService->generateRegistrationChallenge($user);
        $response->withStatus(200);
        $response->getBody()->write($challenge);
        return $response;
    }

    public function validateRegistrationChallenge(Request $request, Response $response)
    {
        $user = $this->authService->getAuthUser();
        if(empty($user)) {
            $response->withStatus(400);
            $response->withAddedHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode([
                'error' => 'Usuário não autenticado',
                'success' => false
            ]));
            return $response;
        }

        $res = $this->authService->validateAndSaveRegistrationChallenge(
            $request->getBody()->getContents(),
            $user->id
        );

        if(empty($res)) {
            $response->withStatus(400);
            $response->withAddedHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode([
                'error' => 'Falha na validação',
                'success' => false
            ]));
            return $response;
        }

        $response->withStatus(200);
        $response->withAddedHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode([
            'success' => true
        ]));
        return $response;
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
    ) {
        return $view->render($response, 'singup.html.twig', []);
    }

    public function singupSubmit(
        Request $request,
        Response $response,
        Twig $view,
        UserRepository $repo
    ) {
        $data = $request->getBody()->getContents();
        $data = json_decode($data, true);
        if(empty($data['user'])) {
            $response->withStatus(400);
            $response->withAddedHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode([
                'error' => 'Usuário é obrigatório',
                'success' => false
            ]));
            return $response;
        }
        $newUser = new User();
        $newUser->username = $data['user'];
        $newUser->color = '#FF0000';
        try {
            $r = $repo->save($newUser);
        } catch(UserActionException $ex) {
            $response->withStatus(400);
            $response->withAddedHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode([
                'error' => $ex->getMessage(),
                'success' => false
            ]));
            return $response;
        } catch(UserActionException $ex) {
            $response->withStatus(500);
            $response->withAddedHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode([
                'error' => $ex->getMessage(),
                'success' => false
            ]));
            return $response;
        }

        if(!$r) {
            $response->withStatus(400);
            $response->withAddedHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode([
                'error' => 'Erro ao salvar usuário',
                'success' => false
            ]));
            return $response;
        }

        $this->authService->login($newUser->username);

        $response->withStatus(201);
        $response->withAddedHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode([
            'success' => true,
        ]));
        return $response;
    }
}
