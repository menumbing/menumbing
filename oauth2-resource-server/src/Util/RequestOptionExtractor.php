<?php

declare(strict_types=1);

namespace Menumbing\OAuth2\ResourceServer\Util;

use Hyperf\HttpServer\Router\Dispatched;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class RequestOptionExtractor
{
    public static function getOption(ServerRequestInterface $request, string $key, mixed $default = null): mixed
    {
        $dispatched = $request->getAttribute(Dispatched::class);

        return $dispatched->handler?->options[$key] ?? $default;
    }

    public static function hasOption(ServerRequestInterface $request, string $key): bool
    {
        $dispatched = $request->getAttribute(Dispatched::class);

        return null !== ($dispatched->handler?->options[$key] ?? null);
    }
}