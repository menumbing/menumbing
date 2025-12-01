<?php

declare(strict_types=1);

namespace Menumbing\EventStream\Listener;

use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Event\ListenerProvider;
use Hyperf\Framework\Event\BeforeMainServerStart;
use Hyperf\Server\Event\MainCoroutineServerStart;
use Menumbing\EventStream\Annotation\ProducedEvent;
use Menumbing\EventStream\Factory\StreamFactory;
use Menumbing\EventStream\Handler\ProduceEventHandler;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class RegisterProducers implements ListenerInterface
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function listen(): array
    {
        return [
            BeforeMainServerStart::class,
            MainCoroutineServerStart::class,
        ];
    }

    public function process(object $event): void
    {
        $listenerProvider = $this->container->get(ListenerProviderInterface::class);
        $streamFactory = $this->container->get(StreamFactory::class);
        $eventDispatcher = $this->container->get(EventDispatcherInterface::class);

        if ($listenerProvider instanceof ListenerProvider) {
            foreach ($this->getAnnotations() as $class => $annotation) {
                $driver = $streamFactory->get($annotation->driver);
                $listenerProvider->on($class, new ProduceEventHandler($driver, $annotation, $eventDispatcher), -999999999);
            }
        }
    }

    /**
     * @return array<string, ProducedEvent>
     */
    private function getAnnotations(): array
    {
        return AnnotationCollector::getClassesByAnnotation(ProducedEvent::class) ?? [];
    }
}
