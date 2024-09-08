<?php

namespace Matheus\PasskeyPhp\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Matheus\PasskeyPhp\Exception\UserActionException;
use Matheus\PasskeyPhp\Model\User;

class UserRepository
{
    public function __construct(protected Connection $conn)
    {
    }

    public function getById(int $id): ?User
    {
        $data = $this->conn->createQueryBuilder()
                ->select('*')
                ->from('users')
                ->where('id = :id')
                ->setParameter('id', $id)
                ->executeQuery()
                ->fetchAssociative();

        if(empty($data)) {
            return null;
        }
        $res = new User();
        $res->populateFromArray($data);
        return $res;
    }

    public function getByUsername(string $user): ?User
    {
        $data = $this->conn->createQueryBuilder()
                ->select('*')
                ->from('users')
                ->where('username = :user')
                ->setParameter('user', $user)
                ->executeQuery()
                ->fetchAssociative();

        if(empty($data)) {
            return null;
        }
        $res = new User();
        $res->populateFromArray($data);
        return $res;
    }

    public function save(User $user): bool
    {
        try {
            $n = $this->conn->insert('users', [
                'username' => $user->username,
                'color' => $user->color
            ]);
        } catch(UniqueConstraintViolationException $ex) {
            throw new UserActionException('Nome de usuário já está sendo utilizado');
        } catch(\Exception $ex) {
            return false;
        }

        if($n > 0) {
            $user->id = $this->conn->lastInsertId();
        }

        return $n > 0;
    }

    public function update(User $user): bool
    {
        if(empty($user->id)) {
            return false;
        }
        try {
            $n = $this->conn->update('users', [
                'username' => $user->username,
                'color' => $user->color
            ], [
                'id' => $user->id
            ]);
        } catch(\Exception $ex) {
            return false;
        }

        return $n > 0;
    }
}
