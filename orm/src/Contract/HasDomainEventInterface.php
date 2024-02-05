<?php

declare(strict_types=1);

namespace Menumbing\Orm\Contract;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface HasDomainEventInterface
{
    /**
     * Record a domain event.
     *
     * @param  object  $event  The event object to be recorded.
     *
     * @return static  The current instance of the class.
     */
    public function recordThat(object $event): static;

    /**
     * Releases all domain events that have been accumulated.
     *
     * @return array Returns an array of domain events that have been released.
     */
    public function releasePendingDomainEvents(): array;
}
