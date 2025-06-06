<?php

declare(strict_types=1);

namespace Menumbing\Contract\EventStream;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface IdProviderInterface
{
    public function newId(): string;
}
