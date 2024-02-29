<?php

declare(strict_types=1);

namespace Menumbing\GraphQL\Annotation;

use Attribute;
use BadMethodCallException;
use TheCodingMachine\GraphQLite\Annotations\ParameterAnnotationInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
#[Attribute(Attribute::TARGET_PARAMETER|Attribute::TARGET_PROPERTY|Attribute::IS_REPEATABLE)]
class Validate implements ParameterAnnotationInterface
{
    public function __construct(public readonly string $rule, public readonly array $messages = [])
    {
    }

    public function getTarget(): string
    {
        throw new BadMethodCallException('Not supported.');
    }
}
