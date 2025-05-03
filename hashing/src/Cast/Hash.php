<?php

declare(strict_types=1);

namespace Menumbing\Hashing\Cast;

use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\CastsInboundAttributes;
use HyperfExtension\Hashing\Contract\HashInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class Hash implements CastsInboundAttributes
{
    public function __construct(protected readonly string $driver = 'bcrypt')
    {
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return $this->hash()->getDriver($this->driver)->make($value);
    }

    protected function hash(): HashInterface
    {
        return ApplicationContext::getContainer()->get(HashInterface::class);
    }
}
