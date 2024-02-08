<?php

declare(strict_types=1);

namespace Menumbing\Signature\Model;

use DateTime;
use Hyperf\Database\Model\Concerns\HasUuids;
use HyperfExtension\Auth\Authenticatable;
use HyperfExtension\Auth\Contracts\AuthenticatableInterface;
use Menumbing\Contract\Signature\ClientInterface;
use Menumbing\Orm\Contract\HasDomainEventInterface;
use Menumbing\Orm\Model;
use Menumbing\Orm\Trait\HasDomainEvent;
use Menumbing\Signature\Constant\ClientStatus;
use Menumbing\Signature\Event\ClientGenerated;

/**
 * @property string $id
 * @property string $name
 * @property string $secret
 * @property ClientStatus $status
 * @property DateTime $createdAt
 * @property DateTime $updatedAt
 *
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class Client extends Model implements AuthenticatableInterface, ClientInterface, HasDomainEventInterface
{
    use HasUuids;
    use Authenticatable;
    use HasDomainEvent;

    protected array $casts = [
        'status' => ClientStatus::class,
    ];

    protected array $fillable = [
        'id',
        'name',
        'secret',
        'status',
    ];

    protected string $secretKey = 'secret';

    public static function generate(string $name): static
    {
        $client = new static();
        $client->fill([
            'id' => $client->newUniqueId(),
            'name' => $name,
            'status' => ClientStatus::ENABLED,
        ]);

        $client->refreshSecret()->updateTimestamps();

        return $client->recordThat(ClientGenerated::createFromClient($client));
    }

    public function refreshSecret(): static
    {
        $this->secret = hash('sha256', uniqid());

        return $this;
    }

    public function getId(): string
    {
        return $this->getKey();
    }

    public function getSecret(): string
    {
        return $this->getAttribute($this->secretKey);
    }

    public function isEnabled(): bool
    {
        return $this->status === ClientStatus::ENABLED;
    }
}
