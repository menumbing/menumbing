<?php

declare(strict_types=1);

namespace Menumbing\Resource\Strategy;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Menumbing\Resource\Contract\ResourceStrategyInterface;
use Menumbing\Resource\Resource\JsonResource;
use Menumbing\Resource\Trait\InteractWithAcceptHeader;
use Menumbing\Resource\Trait\ResourceOrigin;
use Psr\Container\ContainerInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class JsonResourceStrategy implements ResourceStrategyInterface
{
    use InteractWithAcceptHeader;
    use ResourceOrigin;

    #[Inject]
    protected ContainerInterface $container;

    public function render(mixed $resource): mixed
    {
        return new JsonResource($resource);
    }

    public function supports(mixed $resource): bool
    {
        return !$this->getOrigin($resource) instanceof \Throwable && $this->wantsJson($this->container->get(RequestInterface::class));
    }
}
