<?php

declare(strict_types=1);

namespace Menumbing\EventStream\Factory;

use Hyperf\Contract\ConfigInterface;
use Menumbing\Contract\EventStream\StreamInterface;
use Menumbing\EventStream\Exception\UnknownStreamDriverException;
use Menumbing\Serializer\Factory\SerializerFactory;

use function Hyperf\Support\make;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class StreamFactory
{
    /**
     * @var array<string, StreamInterface>
     */
    protected array $streams = [];

    public function __construct(private ConfigInterface $config, private SerializerFactory $serializerFactory)
    {
    }

    public function get(string $driverName): StreamInterface
    {
        if (array_key_exists($driverName, $this->streams)) {
            return $this->streams[$driverName];
        }

        $config = $this->config->get(sprintf('event_stream.drivers.%s', $driverName), []);
        $driver = $config['driver'] ?? null;

        if (null === $driver) {
            throw new UnknownStreamDriverException($driverName);
        }

        $serializer = $this->serializerFactory->get($this->config->get('event_stream.serialization.serializer', 'default'));

        return $this->streams[$driverName] = make($driver, array_filter([
            'id' => make($config['id'] ?? null),
            'serializer' => $serializer,
            'options' => [
                'serialize_format' => $this->config->get('event_stream.serialization.format', 'json'),
                ...$config['options'] ?? [],
            ],
        ]));
    }
}
