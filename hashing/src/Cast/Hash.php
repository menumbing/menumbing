<?php

declare(strict_types=1);

namespace Menumbing\Hashing\Cast;

use Hyperf\Contract\CastsInboundAttributes;
use Hyperf\Di\Annotation\Inject;
use HyperfExtension\Hashing\Contract\HashInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class Hash implements CastsInboundAttributes
{
    #[Inject]
    protected HashInterface $hash;

    public function __construct(protected readonly string $driver = 'bcrypt')
    {
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return $this->hash->getDriver($this->driver)->make($value);
    }
}
