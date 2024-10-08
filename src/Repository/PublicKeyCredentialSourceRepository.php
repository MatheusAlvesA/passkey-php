<?php

namespace Matheus\PasskeyPhp\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Matheus\PasskeyPhp\Exception\UserActionException;
use Matheus\PasskeyPhp\Model\User;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredentialSource;

class PublicKeyCredentialSourceRepository
{
    protected $serializer;
    public function __construct(protected Connection $conn)
    {
        $attestStmSM = AttestationStatementSupportManager::create();
        $attestStmSM->add(NoneAttestationStatementSupport::create());
        $this->serializer = (new WebauthnSerializerFactory($attestStmSM))->create();
    }

    public function getByCredentialId(string $id): ?PublicKeyCredentialSource
    {
        $data = $this->conn->createQueryBuilder()
                ->select('*')
                ->from('credentials')
                ->where('credential_id = :id')
                ->setParameter('id', base64_encode($id))
                ->executeQuery()
                ->fetchAssociative();

        if(empty($data)) {
            return null;
        }
        return $this->serializer->deserialize(
            $data['credential_data'],
            PublicKeyCredentialSource::class,
            'json'
        );
    }

    /**
     * @return PublicKeyCredentialSource[]
     */
    public function getAllByUserId(int $userId): array
    {
        $data = $this->conn->createQueryBuilder()
                ->select('*')
                ->from('credentials')
                ->where('user_id = :id')
                ->setParameter('id', $userId)
                ->executeQuery()
                ->fetchAllAssociative();

        if(empty($data)) {
            return [];
        }
        return array_map(function ($row) {
            return $this->serializer->deserialize(
                $row['credential_data'],
                PublicKeyCredentialSource::class,
                'json'
            );
        }, $data);
    }

    public function save(PublicKeyCredentialSource $data, int $userId): bool
    {
        try {
            $json = $this->serializer->serialize(
                $data,
                'json',
            );
            $n = $this->conn->insert('credentials', [
                'user_id' => $userId,
                'credential_id' => base64_encode($data->publicKeyCredentialId),
                'credential_data' => $json
            ]);
        } catch(UniqueConstraintViolationException $ex) {
            throw new UserActionException('A chave já está cadastrada.');
        }

        return $n > 0;
    }

    public function update(PublicKeyCredentialSource $data): bool
    {
        $json = $this->serializer->serialize(
            $data,
            'json',
        );
        $n = $this->conn->update('credentials', [
            'credential_id' => base64_encode($data->publicKeyCredentialId),
            'credential_data' => $json
        ], [
            'credential_id' => base64_encode($data->publicKeyCredentialId)
        ]);

        return $n > 0;
    }
}
