<?php

declare(strict_types=1);

namespace Menumbing\GraphQL\Exception;

use Exception;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLExceptionInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class ValidateException extends Exception implements GraphQLExceptionInterface
{
    protected string $argumentName;

    public static function create(string $message, string $argumentName): static
    {
        $exception = new self($message, 422);
        $exception->argumentName = $argumentName;

        return $exception;
    }

    public function isClientSafe(): bool
    {
        return true;
    }


    /**
     * Returns the "extensions" object attached to the GraphQL error.
     *
     * @return array<string, mixed>
     */
    public function getExtensions(): array
    {
        return [
            'argument' => $this->argumentName
        ];
    }
}
