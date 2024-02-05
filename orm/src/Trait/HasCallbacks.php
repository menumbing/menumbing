<?php

declare(strict_types=1);

namespace Menumbing\Orm\Trait;

use Menumbing\Orm\Constant\Callbacks;
use Menumbing\Orm\Model;

/**
 * @mixin Model
 *
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
trait HasCallbacks
{
    protected array $callbacks = [];

    public function addCallback(Callbacks|string $group, callable $callback): static
    {
        $group = $group instanceof Callbacks ? $group->value : $group;

        $this->callbacks[$group] = [
            ...($this->callbacks[$group] ?? []),
            $callback,
        ];

        return $this;
    }

    public function fireCallbacks(Callbacks|string $group): void
    {
        $group = $group instanceof Callbacks ? $group->value : $group;

        do {
            $callbacks = $this->callbacks[$group] ?? [];

            $this->callbacks[$group] = [];

            foreach ($callbacks as $callback) {
                $callback($this);
            }
        } while (count($this->callbacks[$group] ?? []));
    }
}
