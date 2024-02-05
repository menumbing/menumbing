<?php

declare(strict_types=1);

namespace Menumbing\Orm\Listener;

use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ContainerInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;
use Menumbing\Orm\Annotation\AsRepository;
use Menumbing\Orm\Contract\RepositoryFactoryInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
#[Listener]
final class RegisterRepository implements ListenerInterface
{
    public static bool $registered = false;

    #[Inject]
    protected ContainerInterface $container;

    public function listen(): array
    {
        return [
            BootApplication::class,
        ];
    }

    public function process(object $event): void
    {
        if (self::$registered) {
            return;
        }

        $annotationRepositories = $this->getAnnotationRepositories();

        foreach ($annotationRepositories as $repositoryClass => $annotation) {
            if (is_string($repositoryClass)) {
                $serviceName = $annotation->serviceName ?? $repositoryClass;

                $this->container->define($serviceName, function () use ($annotation, $repositoryClass) {
                    $container = ApplicationContext::getContainer();
                    $factory = $container->get(RepositoryFactoryInterface::class);

                    return $factory->create($annotation->modelClass, $repositoryClass);
                });
            }
        }

        self::$registered = true;
    }

    /**
     * @return array<string, AsRepository>
     */
    private function getAnnotationRepositories(): array
    {
        return AnnotationCollector::getClassesByAnnotation(AsRepository::class);
    }
}
