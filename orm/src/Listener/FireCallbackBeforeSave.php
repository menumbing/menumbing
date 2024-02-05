<?php

declare(strict_types=1);

namespace Menumbing\Orm\Listener;

use Hyperf\Database\Model\Events\Saving;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Menumbing\Orm\Constant\Callbacks;
use Menumbing\Orm\Model;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
#[Listener]
final class FireCallbackBeforeSave implements ListenerInterface
{
    public function listen(): array
    {
        return [
            Saving::class,
        ];
    }

    /**
     * @param  Saving|mixed  $event
     *
     * @return void
     */
    public function process(object $event): void
    {
        $model = $event->getModel();

        if ($model instanceof Model) {
            $model->fireCallbacks(Callbacks::BEFORE_SAVE->value);
        }
    }
}
