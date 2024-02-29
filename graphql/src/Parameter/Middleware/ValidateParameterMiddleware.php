<?php

declare(strict_types=1);

namespace Menumbing\GraphQL\Parameter\Middleware;

use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Menumbing\GraphQL\Annotation\Validate;
use Menumbing\GraphQL\Exception\InvalidValidateAnnotationException;
use Menumbing\GraphQL\Parameter\ValidateParameter;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\Type;
use ReflectionParameter;
use TheCodingMachine\GraphQLite\Annotations\ParameterAnnotations;
use TheCodingMachine\GraphQLite\Mappers\Parameters\ParameterHandlerInterface;
use TheCodingMachine\GraphQLite\Mappers\Parameters\ParameterMiddlewareInterface;
use TheCodingMachine\GraphQLite\Parameters\InputTypeParameterInterface;
use TheCodingMachine\GraphQLite\Parameters\ParameterInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class ValidateParameterMiddleware implements ParameterMiddlewareInterface
{
    public function __construct(protected ValidatorFactoryInterface $validatorFactory)
    {
    }

    public function mapParameter(ReflectionParameter $refParameter, DocBlock $docBlock, ?Type $paramTagType, ParameterAnnotations $parameterAnnotations, ParameterHandlerInterface $next): ParameterInterface
    {
        $validateAnnotations = $parameterAnnotations->getAnnotationsByType(Validate::class);

        $parameter = $next->mapParameter($refParameter, $docBlock, $paramTagType, $parameterAnnotations);

        if (empty($validateAnnotations)) {
            return $parameter;
        }

        if (!$parameter instanceof InputTypeParameterInterface) {
            throw InvalidValidateAnnotationException::canOnlyValidateInputType($refParameter);
        }

        // Let's wrap the ParameterInterface into a ParameterValidator.
        $rules = array_map(fn(Validate $validateAnnotation): string => $validateAnnotation->rule, $validateAnnotations);
        $messages = array_reduce($validateAnnotations, fn(array $carry, Validate $validateAnnotation): array => [...$carry, ...$validateAnnotation->messages], []);

        return new ValidateParameter($parameter, $refParameter->getName(), implode('|', $rules), $messages, $this->validatorFactory);
    }
}
