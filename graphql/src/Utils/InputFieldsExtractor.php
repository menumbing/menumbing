<?php

declare(strict_types=1);

namespace Menumbing\GraphQL\Utils;

use ReflectionObject;
use ReflectionProperty;
use TheCodingMachine\GraphQLite\Annotations\Field;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class InputFieldsExtractor
{
    private static array $cache = [];

    public static function extractFields(object $input): array
    {
        $reflectionObject = new ReflectionObject($input);
        $cacheKey = $reflectionObject->getName();

        if (static::hasCache($cacheKey)) {
            return static::getCache($cacheKey);
        }

        $fields = array_reduce(
            $reflectionObject->getProperties(),
            static function (array $carry, ReflectionProperty $property) use ($input) {
                if (count($property->getAttributes(Field::class)) <= 0) {
                    return $carry;
                }

                return [...$carry, $property->name];
            },
            []
        );

        return static::setCache($cacheKey, $fields);
    }

    public static function extractValues(object $input): array
    {
        $fields = static::extractFields($input);
        $reflectionObject = new ReflectionObject($input);
        $values = [];

        foreach ($fields as $field) {
            if ($reflectionObject->hasProperty($field)) {
                $property = $reflectionObject->getProperty($field);
                if (!$property->isInitialized($input)) {
                    continue;
                }

                $values[$field] = $property->getValue($input);

                continue;
            }

            $values[$field] = null;
        }

        return $values;
    }

    public static function setCache(string $key, array $value): array
    {
        static::$cache[$key] = $value;

        return $value;
    }

    public static function getCache(string $key): array
    {
        return static::$cache[$key];
    }

    public static function hasCache(string $key): bool
    {
        return array_key_exists($key, static::$cache);
    }
}
