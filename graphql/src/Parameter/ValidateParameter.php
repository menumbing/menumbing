<?php

declare(strict_types=1);

namespace Menumbing\GraphQL\Parameter;

use GraphQL\Error\ClientAware;
use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Menumbing\GraphQL\Exception\ValidateException;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLAggregateException;
use TheCodingMachine\GraphQLite\Parameters\InputTypeParameterInterface;
use Throwable;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class ValidateParameter implements InputTypeParameterInterface
{
    public function __construct(
        protected InputTypeParameterInterface $parameter,
        protected string $parameterName,
        protected string $rules,
        protected array $messages,
        protected ValidatorFactoryInterface $validatorFactory,
    ) {
    }

    public function resolve(?object $source, array $args, mixed $context, ResolveInfo $info): mixed
    {
        $value = $this->parameter->resolve($source, $args, $context, $info);

        $validator = $this->validatorFactory->make([$this->parameterName => $value], [$this->parameterName => $this->rules], $this->messages);

        if ($validator->fails()) {
            /** @var Throwable&ClientAware[] $errorMessages */
            $errorMessages = [];

            foreach ($validator->errors()->toArray() as $field => $errors) {
                foreach ($errors as $error) {
                    $errorMessages[] = ValidateException::create($error, $field);
                }
            }

            GraphQLAggregateException::throwExceptions($errorMessages);
        }

        return $value;
    }

    public function getType(): InputType&Type
    {
        return $this->parameter->getType();
    }

    public function hasDefaultValue(): bool
    {
        return $this->parameter->hasDefaultValue();
    }

    public function getDefaultValue(): mixed
    {
        return $this->parameter->getDefaultValue();
    }
}
