<?php

declare(strict_types=1);

namespace Menumbing\Signature\Command;

use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Menumbing\Signature\Contract\ClientRepositoryInterface;
use Menumbing\Signature\Model\Client;
use Psr\Container\ContainerInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
#[Command]
class GenerateClientCommand extends HyperfCommand
{
    protected ?string $signature = 'gen:client';

    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $client = $this->clientRepository()->save(
            Client::generate($this->alwaysAsk('Please enter the client name'))
        );

        $this->info('Client ID: ' . $client->getId());
        $this->info('Client Secret: ' . $client->getSecret());
    }

    protected function clientRepository(): ClientRepositoryInterface
    {
        return $this->container->get(ClientRepositoryInterface::class);
    }

    protected function alwaysAsk(string $question): string
    {
        do {
            $value = $this->ask($question);
        } while (empty($value));

        return $value;
    }
}
