<?php

declare(strict_types=1);

namespace Menumbing\Resource\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function Hyperf\Config\config;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class AppendServerNameMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        return $response->withHeader('Server', config('resource.server_name', 'Hyperf'));
    }
}
