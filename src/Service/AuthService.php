<?php

namespace Matheus\PasskeyPhp\Service;

use Cose\Algorithms;
use Matheus\PasskeyPhp\Model\User;
use Matheus\PasskeyPhp\Repository\PublicKeyCredentialSourceRepository;
use Matheus\PasskeyPhp\Repository\UserRepository;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredential;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

class AuthService
{
    /** @var AttestationStatementSupportManager */
    protected $attestStmSM;
    protected $serializer;
    protected $csmFactory;
    public function __construct(protected UserRepository $repo, protected PublicKeyCredentialSourceRepository $credRepo)
    {
        $this->attestStmSM = AttestationStatementSupportManager::create();
        $this->attestStmSM->add(NoneAttestationStatementSupport::create());
        $this->serializer = (new WebauthnSerializerFactory($this->attestStmSM))->create();
        $this->csmFactory = new CeremonyStepManagerFactory();
    }

    public function generateAuthenticationChallenge(User $user): string
    {
        $publicKeyCredentialSources = $this->credRepo->getAllByUserId($user->id);
        $allowedCredentials = array_map(
            static function (PublicKeyCredentialSource $credential): PublicKeyCredentialDescriptor {
                return $credential->getPublicKeyCredentialDescriptor();
            },
            $publicKeyCredentialSources
        );

        $publicKeyCredentialRequestOptions = PublicKeyCredentialRequestOptions::create(
            random_bytes(32), // Challenge
            allowCredentials: $allowedCredentials,
            userVerification: PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED
        );

        $json = $this->serializer->serialize(
            $publicKeyCredentialRequestOptions,
            'json',
            [ // Optional
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
                JsonEncode::OPTIONS => JSON_THROW_ON_ERROR,
            ]
        );

        session_start();
        $_SESSION = [
            'auth_try_user_id' => $user->id,
            'last_request_auth_options' => $json
        ];

        return $json;
    }

    public function validateAuthenticationChallenge(string $res): bool
    {
        /** @var PublicKeyCredential */
        $publicKeyCredential = $this->serializer->deserialize($res, PublicKeyCredential::class, 'json');

        if (!($publicKeyCredential->response instanceof AuthenticatorAssertionResponse)) {
            return false;
        }
        $publicKeyCredentialSource = $this->credRepo->getByCredentialId(
            $publicKeyCredential->rawId
        );
        if(empty($publicKeyCredentialSource)) {
            return false;
        }

        $authenticatorAssertionResponseValidator = AuthenticatorAssertionResponseValidator::create();
        session_start();
        $json = $_SESSION['last_request_auth_options'];
        if(empty($json) || empty($_SESSION['auth_try_user_id'])) {
            return false;
        }
        $publicKeyCredentialRequestOptions = $this->serializer->deserialize(
            $json,
            PublicKeyCredentialRequestOptions::class,
            'json'
        );

        $publicKeyCredentialSource = $authenticatorAssertionResponseValidator->check(
            $publicKeyCredentialSource,
            $publicKeyCredential->response,
            $publicKeyCredentialRequestOptions,
            'passkey.matheusalves.com.br',
            $_SESSION['auth_try_user_id']
        );

        $this->credRepo->update($publicKeyCredentialSource);

        $_SESSION = ['user_id' => $_SESSION['auth_try_user_id']];
        session_commit();

        return true;
    }

    public function generateRegistrationChallenge(User $user): string
    {
        $rpEntity = PublicKeyCredentialRpEntity::create(
            'Passkey',
            'passkey.matheusalves.com.br',
        );
        $userEntity = PublicKeyCredentialUserEntity::create(
            $user->username,
            (string) $user->id,
            $user->username,
        );

        $publicKeyCredentialCreationOptions = PublicKeyCredentialCreationOptions::create(
            $rpEntity,
            $userEntity,
            random_bytes(16),
            [
                PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_ES256K), // More interesting algorithm
                PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_ES256),  //      ||
                PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_RS256),  //      ||
                PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_PS256),  //      \/
                PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_ED256),  // Less interesting algorithm
            ]
        );

        $json = $this->serializer->serialize(
            $publicKeyCredentialCreationOptions,
            'json',
            [ // Optional
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
                JsonEncode::OPTIONS => JSON_THROW_ON_ERROR,
            ]
        );

        session_start();
        $_SESSION['last_creation_options'] = $json;

        return $json;
    }

    public function validateAndSaveRegistrationChallenge(string $res, int $userId): ?PublicKeyCredentialSource
    {
        $publicKeyCredential = $this->serializer->deserialize(
            $res,
            PublicKeyCredential::class,
            'json'
        );

        if (!($publicKeyCredential->response instanceof AuthenticatorAttestationResponse)) {
            return null;
        }

        $authenticatorAttestationResponseValidator = AuthenticatorAttestationResponseValidator::create(
            $this->attestStmSM
        );

        session_start();
        $json = $_SESSION['last_creation_options'];
        $publicKeyCredentialCreationOptions = $this->serializer->deserialize(
            $json,
            PublicKeyCredentialCreationOptions::class,
            'json'
        );

        $publicKeyCredentialSource = $authenticatorAttestationResponseValidator->check(
            $publicKeyCredential->response,
            $publicKeyCredentialCreationOptions,
            'passkey.matheusalves.com.br'
        );

        if(!$this->credRepo->save($publicKeyCredentialSource, $userId)) {
            return null;
        }

        return $publicKeyCredentialSource;
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
