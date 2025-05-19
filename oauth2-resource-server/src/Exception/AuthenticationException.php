<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\Resource\Exception;

use Exception;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class AuthenticationException extends Exception
{
    public function __construct($message = 'Unauthenticated.', $code = 401)
    {
        parent::__construct($message, $code);
    }
}
