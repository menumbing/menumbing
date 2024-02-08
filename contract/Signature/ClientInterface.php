<?php

declare(strict_types=1);

namespace Menumbing\Contract\Signature;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface ClientInterface
{
    public function getId(): string;

    public function getSecret(): string;

    public function isEnabled(): bool;
}
