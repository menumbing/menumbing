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
use Hyperf\Database\PgSQL\PostgreSqlConnection as BasePostgreSqlConnection;
use Menumbing\Database\Resilience\PostgreSqlConnection;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use stdClass;
use Throwable;

class PostgreSqlConnectionTest extends AbstractTestCase
{
    private PostgreSqlConnection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(BasePostgreSqlConnection::class)) {
            $this->markTestSkipped('hyperf/database-pgsql is not installed.');
        }

        $this->connection = new PostgreSqlConnection($this->pdo(), 'hyperf', '', ['name' => 'default']);
    }

    public function testItReplacesTheConnectionOfThePgSqlComponent(): void
    {
        $this->assertInstanceOf(BasePostgreSqlConnection::class, $this->connection);
        $this->assertNotSame(BasePostgreSqlConnection::class, $this->connection::class);
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

    public static function lostConnectionMessages(): array
    {
        return [
            'server restart' => ['FATAL: terminating connection due to administrator command'],
            'recovery conflict' => ['FATAL: terminating connection due to conflict with recovery'],
            'backend crash' => ['terminating connection because of crash of another server process'],
            'server unreachable' => ['could not connect to server: Is the server running on that host?'],
            'connection refused' => ['connection to server at "127.0.0.1", port 5432 failed: Connection refused'],
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

    private function causedByLostConnection(Throwable $e): bool
    {
        $method = new ReflectionMethod(PostgreSqlConnection::class, 'causedByLostConnection');
        $method->setAccessible(true);

        return $method->invoke($this->connection, $e);
    }

    private function pdo(): Closure
    {
        return static fn () => new stdClass();
    }
}
