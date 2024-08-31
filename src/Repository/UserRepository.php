<?php

namespace Matheus\PasskeyPhp\Repository;

use Doctrine\DBAL\Connection;
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
}
