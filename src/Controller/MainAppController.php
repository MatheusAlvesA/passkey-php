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
        return $view->render($response, 'login.html.twig', []);
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
            $this->configJsonResponse($response, 'Usuário não autenticado', 400);
            return $response;
        }

        $res = $this->authService->validateAndSaveRegistrationChallenge(
            $request->getBody()->getContents(),
            $user->id
        );

        if(empty($res)) {
            $this->configJsonResponse($response, 'Falha na validação', 400);
            return $response;
        }

        $this->configJsonResponse($response);
        return $response;
    }

    public function generateAuthenticationChallenge(
        Request $request,
        Response $response,
        UserRepository $repo
    ) {
        $user = $repo->getByUsername($request->getQueryParams()['user'] ?? '');
        if(empty($user)) {
            $this->configJsonResponse($response, 'Falha ao autenticar', 400);
            return $response;
        }

        $challenge = $this->authService->generateAuthenticationChallenge($user);
        $response->withStatus(200);
        $response->getBody()->write($challenge);
        return $response;
    }

    public function validateAuthenticationChallenge(Request $request, Response $response)
    {
        $res = $this->authService->validateAuthenticationChallenge($request->getBody()->getContents());

        if(!$res) {
            $this->configJsonResponse($response, 'Falha na autenticação', 401);
            return $response;
        }

        $this->configJsonResponse($response);
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
            $this->configJsonResponse($response, 'Usuário é obrigatório', 400);
            return $response;
        }
        $newUser = new User();
        $newUser->username = $data['user'];
        $newUser->color = '#FF0000';
        try {
            $r = $repo->save($newUser);
        } catch(UserActionException $ex) {
            $this->configJsonResponse($response, $ex->getMessage(), 400);
            return $response;
        } catch(UserActionException $ex) {
            $this->configJsonResponse($response, $ex->getMessage(), 500);
            return $response;
        }

        if(!$r) {
            $this->configJsonResponse($response, 'Erro ao salvar usuário', 400);
            return $response;
        }

        session_start();
        $_SESSION['user_id'] = $newUser->id;

        $this->configJsonResponse($response, null, 201);
        return $response;
    }

    private function configJsonResponse(
        Response $response,
        ?string $errorMessage = null,
        int $statusCode = 200
    ): Response {
        $dataRes = [
            'success' => empty($errorMessage),
        ];
        if(!empty($errorMessage)) {
            $dataRes['error'] = $errorMessage;
        }
        $response->withStatus($statusCode);
        $response->withAddedHeader('Content-Type', 'application/json');
        if(!empty($errorMessage)) {
            $response->getBody()->write(json_encode($dataRes));
        }
        return $response;
    }
}
