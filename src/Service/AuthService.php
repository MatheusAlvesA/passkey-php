<?php

namespace Matheus\PasskeyPhp\Service;

use Matheus\PasskeyPhp\Model\User;
use Matheus\PasskeyPhp\Repository\UserRepository;

class AuthService
{
    public function __construct(protected UserRepository $repo)
    {
    }

    public function login(string $user, string $pass): ?User
    {
        $user = $this->repo->getByUsername($user);
        if(empty($user)) {
            return null;
        }
        if($user->password != $pass) {
            return null;
        }

        session_start();
        $_SESSION['user_id'] = $user->id;
        session_commit();

        return $user;
    }

    public function getAuthUser(): ?User
    {
        session_start();
        if(empty($_SESSION['user_id'])) {
            return null;
        }
        return $this->repo->getById((int) $_SESSION['user_id']);
    }

    public function logout()
    {
        session_start();
        $_SESSION = [];
        session_commit();
    }
}
