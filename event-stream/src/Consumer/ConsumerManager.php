<?php

declare(strict_types=1);

namespace Menumbing\EventStream\Consumer;

use Generator;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Process\ProcessManager;
use Menumbing\EventStream\Annotation\ConsumedEvent;
use Menumbing\EventStream\EventRegistry;
use Menumbing\EventStream\Handler\ConsumerEventHandler;
use Psr\Container\ContainerInterface;

use function Hyperf\Support\make;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class ConsumerManager
{
    public function __construct(
        protected ContainerInterface $container,
        protected ConfigInterface $config,
        protected EventRegistry $eventRegistry,
    ) {
    }

    public function register(): void
    {
        $this->eventRegistry->init();

        foreach ($this->getStreams() as $key => $stream) {
            $process = $this->createProcess($stream);
            $process->name = $key;
            $process->nums = $this->config->get(sprintf('event_stream.consumer.processes.%s', $key), 1);

            ProcessManager::register($process);
        }
    }

    protected function createProcess(array $stream): ConsumerProcess
    {
        $groupName = $this->config->get('event_stream.group_name', 'menumbing');

        return new class($this->container, $stream, $groupName) extends ConsumerProcess {
            public function __construct(
                ContainerInterface $container,
                array $stream,
                string $groupName,
            ) {
                parent::__construct($container);

                $this->streamName = $stream['name'];
                $this->driverName = $stream['driver'];
                $this->groupName = $groupName;
            }

            protected function handler(): callable
            {
                return make(ConsumerEventHandler::class);
            }
        };
    }

    protected function getStreams(): Generator
    {
        $classes = AnnotationCollector::getClassesByAnnotation(ConsumedEvent::class) ?? [];
        $streams = [];

        /** @var ConsumedEvent $annotation */
        foreach ($classes as $annotation) {
            $key = $annotation->driver.':'.$annotation->stream;

            if (array_key_exists($key, $streams)) {
                continue;
            }

            yield $key => [
                'name'   => $annotation->stream,
                'driver' => $annotation->driver,
            ];
        }
    }
}
