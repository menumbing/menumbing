<?php

declare(strict_types=1);

namespace Menumbing\Signature\Repository;

use Menumbing\Contract\Signature\ClientInterface;
use Menumbing\Orm\Repository;
use Menumbing\Signature\Contract\ClientRepositoryInterface;
use Menumbing\Signature\Model\Client;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class ClientRepository extends Repository implements ClientRepositoryInterface
{
    public function generateNewClient(string $name): ClientInterface
    {
        return $this->save(
            Client::generate($name)
        );
    }
}
