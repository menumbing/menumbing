<?php

declare(strict_types=1);

namespace Menumbing\Signature\Contract;

use Menumbing\Contract\Signature\ClientInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface ClientRepositoryInterface
{
    public function generateNewClient(string $name): ClientInterface;
}
