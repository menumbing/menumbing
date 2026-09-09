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

use Closure;
use Hyperf\Database\Connection;
use Hyperf\Database\MySqlConnection;
use Hyperf\Database\PgSQL\PostgreSqlConnection as BasePostgreSqlConnection;
use Hyperf\Database\PgSQL\PostgreSqlSwooleExtConnection as BasePostgreSqlSwooleExtConnection;
use HyperfTest\Database\Resilience\Stubs\EmptyContainer;
use InvalidArgumentException;
use Menumbing\Database\Resilience\Connectors\ConnectionFactory;
use Menumbing\Database\Resilience\PostgreSqlConnection;
use ReflectionMethod;
use ReflectionProperty;
use stdClass;

/**
 * The pgsql component is optional, so every test either works without it or states explicitly why
 * it cannot run. Nothing here opens a real database connection.
 */
class ConnectionFactoryTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->resetConnectionResolvers();
    }

    protected function tearDown(): void
    {
        $this->resetConnectionResolvers();

        parent::tearDown();
    }

    public function testMySqlConnectionsAreDelegatedToTheParentFactory(): void
    {
        $connection = $this->createConnection('mysql');

        $this->assertInstanceOf(MySqlConnection::class, $connection);
        $this->assertSame('hyperf', $connection->getDatabaseName());
    }

    public function testUnknownDriversKeepThrowing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported driver [sqlite]');

        $this->createConnection('sqlite');
    }

    public function testOnlyThePgSqlDriversAreRouted(): void
    {
        $this->assertNull($this->resilientConnectionFactory('mysql'));
        $this->assertNull($this->resilientConnectionFactory('sqlite'));
        $this->assertNull($this->resilientConnectionFactory(''));
    }

    public function testPgSqlIsOnlyRoutedWhenTheComponentIsInstalled(): void
    {
        $factory = $this->resilientConnectionFactory('pgsql');

        if (class_exists(BasePostgreSqlConnection::class)) {
            $this->assertInstanceOf(Closure::class, $factory);
        } else {
            $this->assertNull($factory);
        }
    }

    public function testPgSqlSwooleIsOnlyRoutedWhenTheSwooleConnectionIsUsable(): void
    {
        $factory = $this->resilientConnectionFactory('pgsql-swoole');

        if ($this->swoolePgSqlIsUsable()) {
            $this->assertInstanceOf(Closure::class, $factory);
        } else {
            $this->assertNull($factory);
        }
    }

    public function testPgSqlDriverProducesTheResilientConnection(): void
    {
        if (! class_exists(BasePostgreSqlConnection::class)) {
            $this->markTestSkipped('hyperf/database-pgsql is not installed.');
        }

        $connection = $this->createConnection('pgsql');

        $this->assertInstanceOf(PostgreSqlConnection::class, $connection);
        $this->assertNotSame(BasePostgreSqlConnection::class, $connection::class);
    }

    public function testPgSqlDriverFallsBackToTheParentFactoryWithoutTheComponent(): void
    {
        if (class_exists(BasePostgreSqlConnection::class)) {
            $this->markTestSkipped('hyperf/database-pgsql is installed, the fallback cannot be reached.');
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported driver [pgsql]');

        $this->createConnection('pgsql');
    }

    public function testPgSqlSwooleDriverFallsBackToTheParentFactoryWithoutTheComponent(): void
    {
        if ($this->swoolePgSqlIsUsable()) {
            $this->markTestSkipped('The swoole pgsql connection is usable, the fallback cannot be reached.');
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported driver [pgsql-swoole]');

        $this->createConnection('pgsql-swoole');
    }

    public function testSwooleSupportIsCheckedBeforeTheClassIsAutoloaded(): void
    {
        // Loading Hyperf\Database\PgSQL\PostgreSqlSwooleExtConnection without --enable-swoole-pgsql
        // fatals on the missing SW_PGSQL_ASSOC constant, so the constant decides first.
        $method = new ReflectionMethod(ConnectionFactory::class, 'hasSwoolePgSqlConnection');
        $method->setAccessible(true);

        $this->assertSame(
            defined('SW_PGSQL_ASSOC') && class_exists(BasePostgreSqlSwooleExtConnection::class),
            $method->invoke($this->factory())
        );
    }

    public function testAnApplicationResolverIsNeverReplaced(): void
    {
        $expected = new stdClass();

        Connection::resolverFor('pgsql', static fn () => $expected);
        Connection::resolverFor('pgsql-swoole', static fn () => $expected);

        $this->assertSame($expected, $this->createConnection('pgsql'));
        $this->assertSame($expected, $this->createConnection('pgsql-swoole'));
    }

    public function testAResolverReturningTheComponentConnectionIsUpgraded(): void
    {
        if (! class_exists(BasePostgreSqlConnection::class)) {
            $this->markTestSkipped('hyperf/database-pgsql is not installed.');
        }

        // This is what hyperf/database-pgsql registers from its BootApplication listener.
        Connection::resolverFor(
            'pgsql',
            static fn ($connection, $database, $prefix, $config) => new BasePostgreSqlConnection($connection, $database, $prefix, $config)
        );

        $connection = $this->createConnection('pgsql');

        $this->assertInstanceOf(PostgreSqlConnection::class, $connection);
    }

    public function testAResolverReturningASubclassOfTheComponentConnectionIsKept(): void
    {
        if (! class_exists(BasePostgreSqlConnection::class)) {
            $this->markTestSkipped('hyperf/database-pgsql is not installed.');
        }

        $resolved = $this->createBasePostgreSqlConnection();

        Connection::resolverFor('pgsql', static fn () => $resolved);

        $this->assertSame($resolved, $this->createConnection('pgsql'));
    }

    private function createBasePostgreSqlConnection(): BasePostgreSqlConnection
    {
        return new class ($this->pdo(), 'hyperf', '', []) extends BasePostgreSqlConnection {};
    }

    private function swoolePgSqlIsUsable(): bool
    {
        return defined('SW_PGSQL_ASSOC') && class_exists(BasePostgreSqlSwooleExtConnection::class);
    }

    private function pdo(): Closure
    {
        return static fn () => new stdClass();
    }

    private function factory(): ConnectionFactory
    {
        return new ConnectionFactory(new EmptyContainer());
    }

    private function createConnection(string $driver, string $database = 'hyperf', string $prefix = '', array $config = []): mixed
    {
        $method = new ReflectionMethod(ConnectionFactory::class, 'createConnection');
        $method->setAccessible(true);

        return $method->invoke($this->factory(), $driver, $this->pdo(), $database, $prefix, $config);
    }

    private function resilientConnectionFactory(string $driver): ?Closure
    {
        $method = new ReflectionMethod(ConnectionFactory::class, 'resilientConnectionFactory');
        $method->setAccessible(true);

        return $method->invoke($this->factory(), $driver, $this->pdo(), 'hyperf', '', []);
    }

    private function resetConnectionResolvers(): void
    {
        $property = new ReflectionProperty(Connection::class, 'resolvers');
        $property->setAccessible(true);
        $property->setValue(null, []);
    }
}
