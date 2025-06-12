<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Exception;

use Hyperf\Collection\Arr;
use HyperfExtension\Auth\Exceptions\AuthorizationException;
use Menumbing\Contract\Exception\HasHttpResponseInterface;
use Menumbing\Resource\Trait\ExceptionWithPayload;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class MissingScopeException extends AuthorizationException implements HasHttpResponseInterface
{
    use ExceptionWithPayload;

    public readonly array $scopes;

    public function __construct($scopes = [], $message = 'Token has invalid/missing scope(s).', $code = 403)
    {
        parent::__construct($message, $code);

        $this->scopes = Arr::wrap($scopes);
    }
}
