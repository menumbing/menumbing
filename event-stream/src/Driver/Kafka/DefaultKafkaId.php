<?php

declare(strict_types=1);

namespace Menumbing\EventStream\Driver\Kafka;

use Menumbing\Contract\EventStream\IdProviderInterface;
use Ramsey\Uuid\Uuid;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class DefaultKafkaId implements IdProviderInterface
{
    public function newId(): string
    {
        return Uuid::uuid4()->toString();
    }
}
