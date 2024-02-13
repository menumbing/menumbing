<?php

declare(strict_types=1);

namespace Menumbing\HttpClient\Factory;

use GuzzleHttp\HandlerStack;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Guzzle\MiddlewareInterface;
use Hyperf\Guzzle\PoolHandler;
use Menumbing\Contract\HttpClient\HttpClientHandlerFactoryInterface;
use RuntimeException;

use function Hyperf\Support\make;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class GuzzleHttpClientHandlerFactory implements HttpClientHandlerFactoryInterface
{
    public function __construct(private readonly array $options = [])
    {
    }

    public function create(array $middlewares = [], array $options = []): callable
    {
        $handler = null;
        if (Coroutine::inCoroutine()) {
            $handler = make(PoolHandler::class, [
                'option' => array_replace($this->options, $options)
            ]);
        }

        $stack = HandlerStack::create($handler);

        foreach ($middlewares as $name => $option) {
            if (!in_array(MiddlewareInterface::class, class_implements($option[0]))) {
                throw new RuntimeException(sprintf(
                    'Middleware "%s" should implement "%s.',
                    $option[0],
                    MiddlewareInterface::class
                ));
            }

            $middleware = make($option[0], $option[1] ?? []);
            $stack->push($middleware->getMiddleware(), $name);
        }

        return $stack;
    }
}
