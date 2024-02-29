<?php

declare(strict_types=1);

namespace Menumbing\GraphQL\Validator;

use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Menumbing\Contract\GraphQL\HasValidationInterface;
use Menumbing\GraphQL\Annotation\Validate;
use Menumbing\GraphQL\Exception\ValidateException;
use Menumbing\GraphQL\Utils\InputFieldsExtractor;
use ReflectionObject;
use ReflectionProperty;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLAggregateException;
use TheCodingMachine\GraphQLite\Types\InputTypeValidatorInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class InputTypeValidator implements InputTypeValidatorInterface
{
    protected array $cache = [];

    public function __construct(protected ValidatorFactoryInterface $validatorFactory)
    {
    }

    public function isEnabled(): bool
    {
        return true;
    }

    public function validate(object $input): void
    {
        $reflectionObject = new ReflectionObject($input);
        $validation = $this->parseValidation($reflectionObject, $input);

        if (count($validation['rules'])) {
            $values = InputFieldsExtractor::extractValues($input);

            $validator = $this->validatorFactory->make($values, $validation['rules'], $validation['messages']);

            if ($validator->fails()) {
                $errorMessages = [];

                foreach ($validator->errors()->toArray() as $field => $errors) {
                    foreach ($errors as $error) {
                        $errorMessages[] = ValidateException::create($error, $field);
                    }
                }

                GraphQLAggregateException::throwExceptions($errorMessages);
            }
        }
    }

    protected function parseValidation(ReflectionObject $reflectionObject, object $input): array
    {
        if ($input instanceof HasValidationInterface) {
            return [
                'rules' => $input->validationRules(),
                'messages' => $input->validationMessages(),
            ];
        }

        return $this->getAnnotationValidations($reflectionObject);
    }

    protected function getAnnotationValidations(ReflectionObject $reflectionObject): array
    {
        $cacheKey = $this->getCacheKey($reflectionObject, 'validations');

        if (InputFieldsExtractor::hasCache($cacheKey)) {
            return InputFieldsExtractor::getCache($cacheKey);
        }

        $validation = array_reduce(
            $reflectionObject->getProperties(),
            static function (array $carry, ReflectionProperty $property) {
                if (count($attributes = $property->getAttributes(Validate::class)) <= 0) {
                    return $carry;
                }

                $rules = [];
                $messages = [];

                foreach ($attributes as $attribute) {
                    /** @var Validate $annotation */
                    $annotation = $attribute->newInstance();

                    $rules[] = $annotation->rule;

                    foreach ($annotation->messages as $key => $message) {
                        $messages[$property->name . '.' . $key] = $message;
                    }
                }

                return [
                    'rules'    => [...$carry['rules'], $property->name => implode('|', $rules)],
                    'messages' => [...$carry['messages'], ...$messages],
                ];
            },
            ['rules' => [], 'messages' => []]
        );

        return InputFieldsExtractor::setCache($cacheKey, $validation);
    }

    protected function setCache(ReflectionObject $reflectionObject, string $type, mixed $value): mixed
    {
        $this->cache[$this->getCacheKey($reflectionObject, $type)] = $value;

        return $value;
    }

    protected function getCacheKey(ReflectionObject $reflectionObject, string $type): string
    {
        return $reflectionObject->getName().'_'.$type;
    }
}
