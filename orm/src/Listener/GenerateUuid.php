<?php

declare(strict_types=1);

namespace Menumbing\Orm\Listener;

use Hyperf\Database\Model\Concerns\HasUuids;
use Hyperf\Database\Model\Events\Creating;
use Hyperf\Database\Model\Events\Event;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
#[Listener]
final class GenerateUuid implements ListenerInterface
{
    public function listen(): array
    {
        return [
            Creating::class,
        ];
    }

    /**
     * @param  Event|mixed  $event
     *
     * @return void
     */
    public function process(object $event): void
    {
        $model = $event->getModel();

        if (in_array(HasUuids::class, class_uses($model))) {
            if (null === $model->getKey()) {
                $model->setAttribute($model->getKeyName(), $model->newUniqueId());
            }
        }
    }
}
