<?php

declare(strict_types=1);

namespace Menumbing\Contract\Hashing;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface HasInterface extends DriverInterface
{
    /**
     * Get a driver instance.
     */
    public function getDriver(?string $name = null): DriverInterface;
}
