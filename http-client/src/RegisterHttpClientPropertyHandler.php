<?php

declare(strict_types=1);

namespace Menumbing\HttpClient;

use Hyperf\Context\ApplicationContext;
use Hyperf\Di\Definition\PropertyHandlerManager;
use Hyperf\Di\Exception\NotFoundException;
use Hyperf\Di\ReflectionManager;
use Menumbing\HttpClient\Annotation\HttpClient;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class RegisterHttpClientPropertyHandler
{
    public static bool $registered = false;

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        PropertyHandlerManager::register(HttpClient::class, function ($object, $currentClassName, $targetClassName, $property, $annotation) {
            if (!$annotation instanceof HttpClient) {
                return;
            }

            $reflectionProperty = ReflectionManager::reflectProperty($currentClassName, $property);
            $container = ApplicationContext::getContainer();

            if (!$container->has($annotation->httpClientId)) {
                throw new NotFoundException(sprintf('No http client found for "%s".', $annotation->httpClientId));
            }

            $reflectionProperty->setValue($object, $container->get($annotation->httpClientId));
        });

        self::$registered = true;
    }
}
