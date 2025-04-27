<?php

declare(strict_types=1);

namespace Menumbing\Hashing;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
abstract class Hash
{
    /**
     * Get information about the given hashed value.
     */
    public function info(string $hashedValue): array
    {
        return password_get_info($hashedValue);
    }

    /**
     * Check the given plain value against a hash.
     */
    public function check(string $value, string $hashedValue, array $options = []): bool
    {
        if (strlen($hashedValue) === 0) {
            return false;
        }

        return password_verify($value, $hashedValue);
    }
}
