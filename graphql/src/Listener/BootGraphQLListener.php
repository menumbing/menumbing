<?php

declare(strict_types=1);

namespace Menumbing\GraphQL\Listener;

use Hyperf\Collection\Arr;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Hyperf\Server\Event;
use Hyperf\Server\Server;
use InvalidArgumentException;
use Menumbing\GraphQL\Controller\PrintSchemaController;
use Menumbing\GraphQL\GraphQLServer;
use Psr\Container\ContainerInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class BootGraphQLListener implements ListenerInterface
{
    public function __construct(protected ConfigInterface $config, protected ContainerInterface $container)
    {
    }

    public function listen(): array
    {
        return [
            BootApplication::class,
        ];
    }

    public function process(object $event): void
    {
        if (!$this->config->get('graphql.server.enabled', true)) {
            return;
        }

        $graphqlServer = $this->parseConfig();

        $servers = $this->config->get('server.servers');
        foreach ($servers as $server) {
            if ($server['port'] == $graphqlServer['port']) {
                throw new InvalidArgumentException(sprintf('The graphql server port is invalid. Because it is conflicted with %s server.', $server['name']));
            }
        }

        $this->config->set('server.servers', [...$servers, $graphqlServer]);

        if ($this->config->get('graphql.print_schema.enabled', true)) {
            $this->registerPrintSchemaRouter($graphqlServer['name']);
        }
    }

    protected function registerPrintSchemaRouter(string $serverName): void
    {
        $factory = $this->container->get(DispatcherFactory::class);
        $uri = $this->config->get('graphql.print_schema.uri', '/graphql/schema');
        $middlewares = $this->config->get('graphql.print_schema.middlewares', []);

        $factory->getRouter($serverName)->addRoute(['GET'], $uri, [PrintSchemaController::class, 'print'], [
            'middleware' => $middlewares,
        ]);
    }

    protected function parseConfig(): array
    {
        $options = $this->config->get('graphql.server', []);

        return array_replace(
            [
                'name' => 'graphql',
                'type' => Server::SERVER_HTTP,
                'host' => '0.0.0.0',
                'port' => 4000,
                'sock_type' => SWOOLE_SOCK_TCP,
                'callbacks' => [
                    Event::ON_REQUEST => [GraphQLServer::class, 'onRequest'],
                ],
                'options' => [
                    // Whether to enable request lifecycle event
                    'enable_request_lifecycle' => false,
                ],
            ],
            Arr::except($options, 'enabled'),
        );
    }
}
