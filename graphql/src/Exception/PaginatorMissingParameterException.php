<?php

declare(strict_types=1);

namespace Menumbing\GraphQL\Exception;

use Exception;
use GraphQL\Error\ClientAware;
use TheCodingMachine\GraphQLite\Mappers\CannotMapTypeExceptionInterface;
use TheCodingMachine\GraphQLite\Mappers\CannotMapTypeTrait;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class PaginatorMissingParameterException extends Exception implements ClientAware, CannotMapTypeExceptionInterface
{
    use CannotMapTypeTrait;

    public static function missingLimit(): self
    {
        return new self('In the items field of a paginator, you cannot add a "offset" without also adding a "limit"');
    }

    public static function noSubType(): self
    {
        return new self('Result sets implementing PaginatorInterface need to have a subtype. Please define it using @return annotation.');
    }

    public function isClientSafe(): bool
    {
        return true;
    }

    public function getCategory(): string
    {
        return 'pagination';
    }
}
