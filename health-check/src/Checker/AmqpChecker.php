<?php

declare(strict_types=1);

namespace Menumbing\HealthCheck\Checker;

use Hyperf\Amqp\ConnectionFactory;
use Hyperf\Di\Annotation\Inject;
use Menumbing\Contract\HealthCheck\CheckerInterface;
use Menumbing\Contract\HealthCheck\ResultInterface;
use Menumbing\HealthCheck\Result;
use Psr\Container\ContainerInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class AmqpChecker implements CheckerInterface
{
    const CHECKER_NAME = 'amqp';

    #[Inject]
    protected ContainerInterface $container;

    public function __construct()
    {
        if (! class_exists(ConnectionFactory::class)) {
            throw new \RuntimeException(
                'The "hyperf/amqp" package is required to use AmqpChecker. Please install it via: composer require hyperf/amqp'
            );
        }
    }

    public function getName(): string
    {
        return self::CHECKER_NAME;
    }

    public function check(array $options = []): ResultInterface
    {
        $pool = $options['pool'] ?? 'default';

        try {
            $connection = $this->container->get(ConnectionFactory::class)->getConnection($pool);

            if ($connection->isConnected()) {
                return new Result(self::CHECKER_NAME, true, 'AMQP connection is OK');
            }

            return new Result(self::CHECKER_NAME, false, 'AMQP connection is not connected');
        } catch (\Throwable $e) {
            return new Result(self::CHECKER_NAME, false, $e->getMessage());
        }
    }
}
