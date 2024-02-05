<?php

declare(strict_types=1);

namespace Menumbing\Orm\Listener;

use Hyperf\Database\Model\Events\Saved;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Menumbing\Orm\Constant\Callbacks;
use Menumbing\Orm\Model;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
#[Listener]
final class FireCallbackAfterSave implements ListenerInterface
{
    public function listen(): array
    {
        return [
            Saved::class,
        ];
    }

    /**
     * @param  Saved|mixed  $event
     *
     * @return void
     */
    public function process(object $event): void
    {
        $model = $event->getModel();

        if ($model instanceof Model) {
            $model->fireCallbacks(Callbacks::AFTER_SAVE->value);
        }
    }
}
