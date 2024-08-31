<?php

namespace Matheus\PasskeyPhp\Exception;

/**
 * Essa é uma exceção esperada vinda de uma ação incorreta por parte do usuário
 * Ex: Fazer login com dados incorretos, sua mensagem pode ser exibida no frontend
 */
class UserActionException extends \Exception
{
}
