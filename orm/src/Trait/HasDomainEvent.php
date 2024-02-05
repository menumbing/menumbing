<?php

declare(strict_types=1);

namespace Menumbing\Orm\Trait;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
trait HasDomainEvent
{
    protected array $pendingDomainEvents = [];

    public function recordThat(object $event): static
    {
        $this->pendingDomainEvents[] = $event;

        return $this;
    }

    public function releasePendingDomainEvents(): array
    {
        $events = $this->pendingDomainEvents;
        $this->pendingDomainEvents = [];

        return $events;
    }
}
