<?php

declare(strict_types=1);

namespace Menumbing\HttpClient\Listener;

use Hyperf\Collection\Arr;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ContainerInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BeforeMainServerStart;
use Hyperf\Server\Event\MainCoroutineServerStart;
use Menumbing\Contract\HttpClient\HttpClientFactoryInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
#[Listener]
final class RegisterHttpClientListener implements ListenerInterface
{
    public static bool $registered = false;

    #[Inject]
    private ContainerInterface $container;

    #[Inject]
    private ConfigInterface $config;

    public function listen(): array
    {
        return [
            BeforeMainServerStart::class,
            MainCoroutineServerStart::class,
        ];
    }

    public function process(object $event): void
    {
        if (self::$registered) {
            return;
        }

        foreach ($this->getConfigNamespaces() as $namespace) {
            foreach ($this->config->get(sprintf('%s.http_clients', $namespace), []) as $key => $option) {
                $name = $key;

                if ('http_client' !== $namespace) {
                    $name = $namespace.'.'.$name;
                }

                $this->container->define($name, function () use ($option) {
                    $factory = ApplicationContext::getContainer()->get(HttpClientFactoryInterface::class);

                    return $factory->create($this->makeConfig($option));
                });
            }
        }

        self::$registered = true;
    }

    private function makeConfig(array $option): array
    {
        $config = $this->config->get('http_client');

        $middlewares = [
            ...($config['middlewares'] ?? []),
            ...($option['middlewares'] ?? []),
        ];

        return [
            'options'         => [
                ...($config['defaults'] ?? []),
                ...(Arr::except($option, ['middlewares', 'handler_factory'])),
            ],
            'middlewares'     => $middlewares,
            'handler_factory' => $option['handler_factory'] ?? $config['handler_factory'],
        ];
    }

    private function getConfigNamespaces(): array
    {
        return ['http_client', ...$this->config->get('http_client.config_namespaces', [])];
    }
}
