<?php

declare(strict_types=1);

namespace Menumbing\Orm\Trait;

use Hyperf\Stringable\StrCache;
use Menumbing\Orm\Model;

/**
 * @mixin Model
 *
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
trait HasAttributes
{
    public static bool $camelProperties = true;

    public function setAttribute(string $key, mixed $value): ?static
    {
        return parent::setAttribute($this->snakeIfPossible($key), $value);
    }

    public function getAttribute(string $key): mixed
    {
        return parent::getAttribute($this->snakeIfPossible($key));
    }

    public function getRelationValue(string $key): mixed
    {
        return parent::getRelationValue($this->camelIfPossible($key));
    }

    public function isRelation(string $key): bool
    {
        $key = $this->camelIfPossible($key);

        return method_exists($this, $key) || $this->relationResolver(static::class, $key);
    }

    protected function getRelationshipFromMethod(string $method): mixed
    {
        return parent::getRelationshipFromMethod($this->camelIfPossible($method));
    }

    protected function snakeIfPossible(string $key): string
    {
        return static::$snakeAttributes ? StrCache::snake($key) : $key;
    }

    protected function camelIfPossible(string $key): string
    {
        return static::$camelProperties ? StrCache::camel($key) : $key;
    }
}
