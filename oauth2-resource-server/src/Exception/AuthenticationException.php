<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Exception;

use Exception;
use Menumbing\Contract\Exception\HasHttpResponseInterface;
use Menumbing\Exception\Trait\ExceptionWithPayload;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class AuthenticationException extends Exception implements HasHttpResponseInterface
{
    use ExceptionWithPayload;

    public function __construct($message = 'Unauthenticated.', $code = 401)
    {
        parent::__construct($message, $code);
    }
}
