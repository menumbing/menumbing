<?php

declare(strict_types=1);

namespace Menumbing\HttpClient\Factory;

use GuzzleHttp\Client;
use Menumbing\Contract\HttpClient\HttpClientFactoryInterface;
use Menumbing\Contract\HttpClient\HttpClientHandlerFactoryInterface;
use Psr\Http\Client\ClientInterface;
use RuntimeException;

use function Hyperf\Support\make;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class GuzzleHttpClientFactory implements HttpClientFactoryInterface
{
    public function create(array $parameters = []): ClientInterface
    {
        $handlerClass = $parameters['handler_factory'][0];

        if (!in_array(HttpClientHandlerFactoryInterface::class, class_implements($handlerClass))) {
            throw new RuntimeException(
                sprintf(
                    'Class handler "%s" should implement "%s".',
                    $handlerClass,
                    HttpClientHandlerFactoryInterface::class,
                )
            );
        }

        $handlerFactory = make($handlerClass, $parameters['handler_factory'][1] ?? []);

        $handler = $handlerFactory->create($parameters['middlewares'] ?? []);

        return make(Client::class, [
            'config' => [
                'handler' => $handler,
                ...$parameters['options'],
            ],
        ]);
    }
}
