<?php

declare(strict_types=1);

namespace Menumbing\Signature\Event;

use DateTime;
use Menumbing\Signature\Constant\ClientStatus;
use Menumbing\Signature\Model\Client;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class ClientGenerated
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $secret,
        public readonly ClientStatus $status,
        public readonly DateTime $createdAt,
        public readonly DateTime $updatedAt,
    ) {
    }

    public static function createFromClient(Client $client): static
    {
        return new static(
            $client->id,
            $client->name,
            $client->secret,
            $client->status,
            $client->createdAt,
            $client->updatedAt
        );
    }
}
