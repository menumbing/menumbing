<?php

declare(strict_types=1);

namespace Menumbing\Orm\Listener;

use Hyperf\Database\Model\Events\Created;
use Hyperf\Database\Model\Events\Deleted;
use Hyperf\Database\Model\Events\Event;
use Hyperf\Database\Model\Events\Saved;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Menumbing\Orm\Contract\CacheableInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
#[Listener]
final class InvalidateCache implements ListenerInterface
{
    public function listen(): array
    {
        return [
            //Created::class,
            Saved::class,
            Deleted::class,
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

        if ($model instanceof CacheableInterface) {
            $model->invalidateCache();;
        }
    }
}
