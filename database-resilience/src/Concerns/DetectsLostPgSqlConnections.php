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
namespace Menumbing\Database\Resilience\Concerns;

use Hyperf\Database\Exception\QueryException;
use Hyperf\Stringable\Str;
use Throwable;

/**
 * Extends Hyperf\Database\DetectsLostConnections with the messages produced by the
 * PostgreSQL drivers that Hyperf does not recognise.
 *
 * The trait deliberately does NOT override causedByLostConnection(). Subclasses keep
 * calling parent::causedByLostConnection() first and only fall back to
 * causedByLostPgSqlConnection(), so any message Hyperf adds upstream is still honoured.
 */
trait DetectsLostPgSqlConnections
{
    /**
     * Messages that always mean "this connection cannot be used anymore".
     */
    protected array $pgsqlLostConnectionMessages = [
        // Swoole\Coroutine\PostgreSQL::prepare() and PostgreSQLStatement::execute() both guard with
        //   if (PQisnonblocking($pgsql) == 0 && PQsetnonblocking($pgsql, 1) == -1)
        // libpq returns false / -1 from those two calls only when conn->status == CONNECTION_BAD,
        // and Swoole sets nonblocking once in connect() and never resets it. The notice is therefore
        // an exact "the connection is already dead" signal, but it never reads PQerrorMessage(),
        // so the real cause is swallowed.
        'Cannot set connection to nonblocking mode',
        // prepare_result_parse() default branch.
        'Bad result returned to prepare',
        // PGObject::yield() / swoole_pgsql_coro_onError() reactor failures.
        'swoole_event_add failed',
        'swoole_event_del failed',
        // libpq leaves the connection with a pending result when a previous request was abandoned
        // (coroutine timed out or cancelled), which poisons every following PQsendPrepare().
        'another command is already in progress',
        // libpq connect / socket level.
        'Connection refused',
        'Is the server running on that host',
        'could not connect to server',
        'connection to server at',
        'could not send data to server',
        'could not receive data from server',
        'server process lookup failed',
        'connection not open',
        // PostgreSQL server side, emitted during restarts, failovers and maintenance windows.
        'terminating connection due to administrator command',
        'terminating connection because of crash of another server process',
        'terminating connection due to conflict with recovery',
        'canceling statement due to conflict with recovery',
        'the database system is starting up',
        'the database system is shutting down',
        'the database system is not accepting new connections',
        'the database system is in recovery mode',
        'connection already in progress',
    ];

    /**
     * Determine whether the given exception was caused by a lost PostgreSQL connection
     * that Hyperf\Database\DetectsLostConnections does not recognise.
     */
    protected function causedByLostPgSqlConnection(Throwable $e): bool
    {
        if ($this->causedBySilentPgSqlPrepareFailure($e)) {
            return true;
        }

        return Str::contains($e->getMessage(), $this->pgsqlLostConnectionMessages);
    }

    /**
     * Swoole\Coroutine\PostgreSQL::prepare() can return false without ever assigning
     * $pdo->error, in which case PostgreSqlSwooleExtConnection::prepare() builds a
     * QueryException wrapping an Exception with an empty message. The resulting text is
     * only " (SQL: ...)", which no keyword list can ever match.
     */
    protected function causedBySilentPgSqlPrepareFailure(Throwable $e): bool
    {
        if (! $e instanceof QueryException) {
            return false;
        }

        $previous = $e->getPrevious();

        return $previous !== null && trim($previous->getMessage()) === '';
    }
}
