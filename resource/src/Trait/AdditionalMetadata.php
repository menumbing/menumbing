<?php

declare(strict_types=1);

namespace Menumbing\Resource\Trait;

use Hyperf\Context\ApplicationContext;
use Hyperf\Resource\Json\JsonResource;
use Menumbing\Resource\Contract\RequestIpAddressInterface;

/**
 * @mixin JsonResource
 *
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
trait AdditionalMetadata
{
    protected function withMetadata(): static
    {
        $requestIp = ApplicationContext::getContainer()->get(RequestIpAddressInterface::class);

        $this->additional([
            'meta' => [
                'hostname'  => gethostname(),
                'client_ip' => $requestIp->getClientIp(),
            ],
        ]);

        return $this;
    }
}
