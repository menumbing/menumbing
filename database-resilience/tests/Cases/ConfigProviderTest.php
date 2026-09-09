<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace HyperfTest\Database\Resilience\Cases;

use Hyperf\Config\ProviderConfig;
use Hyperf\Database\Connectors\ConnectionFactory as HyperfConnectionFactory;
use Hyperf\Di\Definition\PriorityDefinition;
use Menumbing\Database\Resilience\ConfigProvider;
use Menumbing\Database\Resilience\Connectors\ConnectionFactory;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionMethod;

class ConfigProviderTest extends AbstractTestCase
{
    public function testItBindsTheHyperfConnectionFactoryToItsOwn(): void
    {
        $config = (new ConfigProvider())();

        $this->assertArrayHasKey('dependencies', $config);
        $this->assertArrayHasKey(HyperfConnectionFactory::class, $config['dependencies']);

        $definition = $config['dependencies'][HyperfConnectionFactory::class];

        $this->assertInstanceOf(PriorityDefinition::class, $definition);
        $this->assertSame(ConnectionFactory::class, $definition->getDefinition());
        $this->assertSame(
            [ConnectionFactory::class => ConfigProvider::CONNECTION_FACTORY_PRIORITY],
            $definition->getDependencies()
        );
    }

    public function testTheBoundFactoryIsAUsableReplacement(): void
    {
        $this->assertTrue(is_subclass_of(ConnectionFactory::class, HyperfConnectionFactory::class));

        // DefinitionSource::normalizeDefinition() unwraps the PriorityDefinition into a class name
        // and only builds a FactoryDefinition when that class has an __invoke(). Everything else is
        // autowired, so adding __invoke() here would silently change how the factory is built.
        $this->assertFalse(method_exists(ConnectionFactory::class, '__invoke'));

        // Autowiring resolves the inherited constructor, which must stay the single container argument.
        $constructor = (new ReflectionClass(ConnectionFactory::class))->getConstructor();

        $this->assertNotNull($constructor);
        $this->assertSame(1, $constructor->getNumberOfRequiredParameters());
        $this->assertSame(ContainerInterface::class, (string) $constructor->getParameters()[0]->getType());
    }

    public function testTheBindingWinsRegardlessOfPackageLoadOrder(): void
    {
        if (! class_exists(ProviderConfig::class)) {
            $this->markTestSkipped('hyperf/config is required to assert the merge behaviour.');
        }

        $ours = (new ConfigProvider())();
        // hyperf/db-connection binds the very same identifier to a plain string.
        $theirs = ['dependencies' => [HyperfConnectionFactory::class => HyperfConnectionFactory::class]];

        foreach ([['ours first', [$ours, $theirs]], ['theirs first', [$theirs, $ours]]] as [$label, $configs]) {
            $merged = $this->merge(...$configs);

            $definition = $merged['dependencies'][HyperfConnectionFactory::class];

            $this->assertInstanceOf(PriorityDefinition::class, $definition, "Lost the binding when loaded {$label}.");
            $this->assertSame(ConnectionFactory::class, $definition->getDefinition(), "Lost the binding when loaded {$label}.");
        }
    }

    public function testAPriorityDefinitionWithAHigherPriorityStillWins(): void
    {
        if (! class_exists(ProviderConfig::class)) {
            $this->markTestSkipped('hyperf/config is required to assert the merge behaviour.');
        }

        $application = [
            'dependencies' => [
                HyperfConnectionFactory::class => new PriorityDefinition(
                    HyperfConnectionFactory::class,
                    ConfigProvider::CONNECTION_FACTORY_PRIORITY + 1
                ),
            ],
        ];

        $merged = $this->merge((new ConfigProvider())(), $application);

        $this->assertSame(
            HyperfConnectionFactory::class,
            $merged['dependencies'][HyperfConnectionFactory::class]->getDefinition(),
            'An application that raises the priority must be able to take the binding back.'
        );
    }

    private function merge(array ...$configs): array
    {
        $method = new ReflectionMethod(ProviderConfig::class, 'merge');
        $method->setAccessible(true);

        return $method->invoke(null, ...$configs);
    }
}
