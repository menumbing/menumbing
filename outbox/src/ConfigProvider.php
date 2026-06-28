<?php

declare(strict_types=1);

namespace Menumbing\Outbox;

use Menumbing\Contract\Outbox\OutboxStorageInterface;
use Menumbing\Outbox\Command\PruneOutboxCommand;
use Menumbing\Outbox\Listener\RegisterOutboxWorker;
use Menumbing\Outbox\Storage\OutboxStorageFactory;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                OutboxStorageInterface::class => OutboxStorageFactory::class,
            ],
            'listeners' => [
                RegisterOutboxWorker::class => 99,
            ],
            'commands' => [
                PruneOutboxCommand::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for outbox.',
                    'source' => __DIR__ . '/../publish/outbox.php',
                    'destination' => BASE_PATH . '/config/autoload/outbox.php',
                ],
                [
                    'id' => 'migration',
                    'description' => 'The migration for outbox messages table.',
                    'source' => __DIR__ . '/../publish/migrations/2024_01_01_000000_create_outbox_messages_table.php',
                    'destination' => BASE_PATH . '/migrations/2024_01_01_000000_create_outbox_messages_table.php',
                ],
            ],
        ];
    }
}
