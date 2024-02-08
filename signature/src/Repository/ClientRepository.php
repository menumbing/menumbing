<?php

declare(strict_types=1);

namespace Menumbing\Signature\Repository;

use Menumbing\Orm\Annotation\AsRepository;
use Menumbing\Orm\Repository;
use Menumbing\Signature\Contract\ClientRepositoryInterface;
use Menumbing\Signature\Model\Client;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
#[AsRepository(modelClass: Client::class, serviceName: ClientRepositoryInterface::class)]
class ClientRepository extends Repository implements ClientRepositoryInterface
{
}
