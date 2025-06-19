<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Util;

use Hyperf\Stringable\Str;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class RequestExtractor
{
    public static function bearerToken(ServerRequestInterface $request): ?string
    {
        $header = $request->getHeaderLine('Authorization');
        if (Str::startsWith($header, 'Bearer ')) {
            return Str::substr($header, 7);
        }

        return null;
    }
}