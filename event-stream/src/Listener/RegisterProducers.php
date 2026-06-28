<?php

declare(strict_types=1);

namespace Menumbing\EventStream\Listener;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ContainerInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Event\ListenerProvider;
use Hyperf\Framework\Event\BeforeMainServerStart;
use Hyperf\Server\Event\MainCoroutineServerStart;
use Menumbing\EventStream\Annotation\ProducedEvent;
use Menumbing\EventStream\Factory\StreamFactory;
use Menumbing\EventStream\Handler\ProduceEventHandler;
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
        $config = $this->container->get(ConfigInterface::class);

        // The produce handler is configurable to support the outbox pattern.
        // When the outbox package is installed, it sets 'produce_handler' in
        // its own config (outbox.php). We check outbox config first, then
        // fall back to event_stream config, then to the default handler.
        $handlerClass = $config->has('outbox.produce_handler')
            ? $config->get('outbox.produce_handler')
            : $config->get('event_stream.produce_handler', ProduceEventHandler::class);

        if ($listenerProvider instanceof ListenerProvider) {
            foreach ($this->getAnnotations() as $class => $annotation) {
                $driver = $streamFactory->get($annotation->driver);

                // Use make() so handler subclasses with #[Inject] properties (e.g.
                // OutboxProduceEventHandler) are resolved through the container.
                $handler = $this->container->make($handlerClass, [
                    $driver,
                    $annotation,
                    $eventDispatcher,
                ]);

                $listenerProvider->on($class, $handler, -999999999);
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
