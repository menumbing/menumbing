<?php

declare(strict_types=1);

namespace Menumbing\Contract\HttpClient;

use Psr\Http\Client\ClientInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface HttpClientFactoryInterface
{
    public function create(array $parameters = []): ClientInterface;
}
