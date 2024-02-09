<?php

declare(strict_types=1);

namespace Menumbing\HttpClient\Middleware;

use GuzzleHttp\Promise\PromiseInterface;
use Hyperf\Guzzle\MiddlewareInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class JsonResponseMiddleware implements MiddlewareInterface
{
    public function getMiddleware(): callable
    {
        return static function (callable $handler): callable {
            return static function (RequestInterface $request, array $options) use ($handler): PromiseInterface {
                return $handler($request, $options)->then(
                    static function (ResponseInterface $response) {
                        if (!empty($contentTypes = $response->getHeader('Content-Type'))) {
                            foreach ($contentTypes as $contentType) {
                                foreach (explode(';', $contentType) as $item) {
                                    if (str_contains('application/json', $item)) {
                                        return json_decode($response->getBody()->getContents(), true);
                                    }
                                }
                            }
                        }

                        return $response;
                    }
                );
            };
        };
    }
}
