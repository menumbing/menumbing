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

use Exception;
use Hyperf\Database\Exception\QueryException;
use HyperfTest\Database\Resilience\Stubs\LostConnectionDetector;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

class DetectsLostPgSqlConnectionsTest extends AbstractTestCase
{
    private LostConnectionDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->detector = new LostConnectionDetector();
    }

    public function testEveryConfiguredMessageIsDetected(): void
    {
        $messages = $this->detector->pgSqlLostConnectionMessages();

        $this->assertNotEmpty($messages);

        foreach ($messages as $message) {
            $this->assertNotSame('', trim($message), 'An empty needle would never match anything.');
            $this->assertTrue(
                $this->detector->causedByLostConnection(new Exception($message)),
                sprintf('Message "%s" is configured but not detected.', $message)
            );
        }
    }

    #[DataProvider('lostConnectionMessages')]
    public function testDetectsPgSqlMessagesHyperfDoesNotKnow(string $message): void
    {
        $this->assertTrue($this->detector->causedByLostConnection(new Exception($message)));
    }

    #[DataProvider('regularQueryErrors')]
    public function testDoesNotClaimALostConnectionForRegularQueryErrors(string $message): void
    {
        $this->assertFalse($this->detector->causedByLostConnection(new Exception($message)));
    }

    #[DataProvider('upstreamLostConnectionMessages')]
    public function testUpstreamDetectionIsStillHonoured(string $message): void
    {
        $this->assertTrue($this->detector->causedByLostConnection(new Exception($message)));
    }

    public function testDetectsTheSilentSwoolePrepareFailure(): void
    {
        // Swoole returns false without touching $pdo->error, and the upstream driver wraps that in
        // an Exception with an empty message, leaving " (SQL: ...)" as the only text.
        $exception = new QueryException('SELECT * FROM "users" WHERE "id" = $1', [1], new Exception(''));

        $this->assertSame(' (SQL: SELECT * FROM "users" WHERE "id" = $1)', $exception->getMessage());
        $this->assertTrue($this->detector->causedByLostConnection($exception));
    }

    public function testDetectsAWhitespaceOnlyPrepareFailure(): void
    {
        $exception = new QueryException('SELECT 1', [], new Exception("  \n "));

        $this->assertTrue($this->detector->causedByLostConnection($exception));
    }

    public function testDoesNotTreatAReportedPrepareFailureAsALostConnection(): void
    {
        $exception = new QueryException(
            'SELECT * FROM "users"',
            [],
            new Exception('SQLSTATE[42P01]: Undefined table: relation "users" does not exist')
        );

        $this->assertFalse($this->detector->causedByLostConnection($exception));
    }

    public function testAnEmptyMessageAloneIsNotALostConnection(): void
    {
        // Only a QueryException can carry the silent prepare failure, an empty message from
        // anywhere else must not be enough to trigger a reconnect.
        $this->assertFalse($this->detector->causedByLostConnection(new Exception('')));
        $this->assertFalse($this->detector->causedByLostConnection(new RuntimeException('')));
    }

    public static function lostConnectionMessages(): array
    {
        return [
            'swoole nonblocking guard' => [
                'swoole_postgresql: Cannot set connection to nonblocking mode',
            ],
            'swoole bad prepare result' => [
                'Bad result returned to prepare',
            ],
            'swoole reactor add failure' => [
                'swoole_event_add failed',
            ],
            'abandoned nonblocking request' => [
                'ERROR: another command is already in progress',
            ],
            'libpq connect refused' => [
                'connection to server at "127.0.0.1", port 5432 failed: Connection refused',
            ],
            'libpq server unreachable' => [
                'could not connect to server: Is the server running on that host and accepting TCP/IP connections?',
            ],
            'libpq send failure' => [
                'could not send data to server: Broken pipe',
            ],
            'libpq receive failure' => [
                'could not receive data from server: Connection reset by peer',
            ],
            'server restart' => [
                'FATAL: terminating connection due to administrator command',
            ],
            'backend crash' => [
                'server closed the connection unexpectedly: terminating connection because of crash of another server process',
            ],
            'recovery conflict' => [
                'FATAL: terminating connection due to conflict with recovery',
            ],
            'statement cancelled by recovery' => [
                'ERROR: canceling statement due to conflict with recovery',
            ],
            'server starting up' => [
                'FATAL: the database system is starting up',
            ],
            'server shutting down' => [
                'FATAL: the database system is shutting down',
            ],
            'server not accepting connections' => [
                'FATAL: the database system is not accepting new connections',
            ],
            'hot standby' => [
                'ERROR: the database system is in recovery mode',
            ],
            'connection not open' => [
                'connection not open',
            ],
        ];
    }

    public static function regularQueryErrors(): array
    {
        return [
            'syntax error' => [
                'SQLSTATE[42601]: Syntax error at or near "SELEC"',
            ],
            'undefined table' => [
                'SQLSTATE[42P01]: Undefined table: relation "users" does not exist',
            ],
            'undefined column' => [
                'SQLSTATE[42703]: Undefined column: column "emial" of relation "users" does not exist',
            ],
            'unique violation' => [
                'SQLSTATE[23505]: Unique violation: duplicate key value violates unique constraint "users_email_key"',
            ],
            'foreign key violation' => [
                'SQLSTATE[23503]: Foreign key violation: insert or update on table "posts" violates foreign key constraint',
            ],
            'not null violation' => [
                'SQLSTATE[23502]: Not null violation: null value in column "email" violates not-null constraint',
            ],
            'check constraint' => [
                'SQLSTATE[23514]: Check violation: new row for relation "orders" violates check constraint',
            ],
            'division by zero' => [
                'SQLSTATE[22012]: Division by zero',
            ],
            'serialization failure' => [
                'SQLSTATE[40001]: Serialization failure: could not serialize access due to concurrent update',
            ],
            'deadlock detected' => [
                'SQLSTATE[40P01]: Deadlock detected',
            ],
            'query timeout' => [
                'ERROR: canceling statement due to statement timeout',
            ],
            'lock timeout' => [
                'ERROR: canceling statement due to lock timeout',
            ],
            'permission denied' => [
                'ERROR: permission denied for table users',
            ],
        ];
    }

    public static function upstreamLostConnectionMessages(): array
    {
        return [
            'server has gone away' => ['MySQL server has gone away'],
            'broken pipe' => ['Broken pipe'],
            'connection timed out' => ['SQLSTATE[HY000] [2002] Connection timed out'],
            'server closed unexpectedly' => ['server closed the connection unexpectedly'],
            'reset by peer' => ['Connection reset by peer'],
        ];
    }
}
