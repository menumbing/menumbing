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
use Exception;
use Hyperf\Database\PgSQL\PostgreSqlSwooleExtConnection as BasePostgreSqlSwooleExtConnection;
use Menumbing\Database\Resilience\PostgreSqlSwooleExtConnection;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use stdClass;
use Throwable;

/**
 * Everything here needs Swoole built with --enable-swoole-pgsql, because the parent class defaults
 * its $fetchMode property to SW_PGSQL_ASSOC and cannot even be loaded without that constant.
 */
class PostgreSqlSwooleExtConnectionTest extends AbstractTestCase
{
    private PostgreSqlSwooleExtConnection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('SW_PGSQL_ASSOC') || ! class_exists(BasePostgreSqlSwooleExtConnection::class)) {
            $this->markTestSkipped('hyperf/database-pgsql and Swoole with --enable-swoole-pgsql are required.');
        }

        $this->connection = new PostgreSqlSwooleExtConnection($this->pdo(), 'hyperf', '', ['name' => 'default']);
    }

    public function testItReplacesTheConnectionOfThePgSqlComponent(): void
    {
        $this->assertInstanceOf(BasePostgreSqlSwooleExtConnection::class, $this->connection);
        $this->assertNotSame(BasePostgreSqlSwooleExtConnection::class, $this->connection::class);
        $this->assertSame('default', $this->connection->getName());
    }

    #[DataProvider('lostConnectionMessages')]
    public function testItDetectsPgSqlLostConnections(string $message): void
    {
        $this->assertTrue($this->causedByLostConnection(new Exception($message)));
    }

    #[DataProvider('regularQueryErrors')]
    public function testItDoesNotReconnectOnRegularQueryErrors(string $message): void
    {
        $this->assertFalse($this->causedByLostConnection(new Exception($message)));
    }

    public function testItKeepsTheDetectionOfTheParentClass(): void
    {
        $this->assertTrue($this->causedByLostConnection(new Exception('MySQL server has gone away')));
        $this->assertTrue($this->causedByLostConnection(new Exception('Broken pipe')));
    }

    #[DataProvider('swoolePgSqlErrors')]
    public function testItOnlyAcceptsAUsableSwooleErrorMessage(mixed $error, ?string $expected): void
    {
        $method = new ReflectionMethod(PostgreSqlSwooleExtConnection::class, 'readSwoolePgSqlError');
        $method->setAccessible(true);

        $this->assertSame($expected, $method->invoke($this->connection, $error));
    }

    public static function lostConnectionMessages(): array
    {
        return [
            'swoole nonblocking guard' => ['swoole_postgresql: Cannot set connection to nonblocking mode'],
            'swoole bad prepare result' => ['Bad result returned to prepare'],
            'swoole reactor failure' => ['swoole_event_add failed'],
            'abandoned request' => ['ERROR: another command is already in progress'],
            'server restart' => ['FATAL: terminating connection due to administrator command'],
            'server shutting down' => ['FATAL: the database system is shutting down'],
        ];
    }

    public static function regularQueryErrors(): array
    {
        return [
            'syntax error' => ['SQLSTATE[42601]: Syntax error at or near "SELEC"'],
            'undefined table' => ['SQLSTATE[42P01]: Undefined table: relation "users" does not exist'],
            'unique violation' => ['SQLSTATE[23505]: Unique violation: duplicate key value'],
            'statement timeout' => ['ERROR: canceling statement due to statement timeout'],
        ];
    }

    public static function swoolePgSqlErrors(): array
    {
        return [
            'not set' => [null, null],
            'empty' => ['', null],
            'whitespace only' => ["\n  ", null],
            'not a string' => [false, null],
            'not a string at all' => [0, null],
            'message' => [
                'server closed the connection unexpectedly  ',
                'server closed the connection unexpectedly',
            ],
        ];
    }

    private function causedByLostConnection(Throwable $e): bool
    {
        $method = new ReflectionMethod(PostgreSqlSwooleExtConnection::class, 'causedByLostConnection');
        $method->setAccessible(true);

        return $method->invoke($this->connection, $e);
    }

    private function pdo(): Closure
    {
        return static fn () => new stdClass();
    }
}
