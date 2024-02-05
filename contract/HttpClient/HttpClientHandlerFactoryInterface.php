<?php

declare(strict_types=1);

namespace Menumbing\Contract\HttpClient;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface HttpClientHandlerFactoryInterface
{
    public function create(array $middlewares = []): callable;
}
