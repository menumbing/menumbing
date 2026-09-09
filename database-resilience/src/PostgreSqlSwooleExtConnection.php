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
namespace Menumbing\Database\Resilience;

use Exception;
use Hyperf\Database\Exception\QueryException;
use Hyperf\Database\PgSQL\PostgreSqlSwooleExtConnection as BasePostgreSqlSwooleExtConnection;
use Menumbing\Database\Resilience\Concerns\DetectsLostPgSqlConnections;
use Swoole\Coroutine\PostgreSQL;
use Swoole\Coroutine\PostgreSQLStatement;
use Throwable;

/**
 * Drop-in replacement for Hyperf\Database\PgSQL\PostgreSqlSwooleExtConnection.
 *
 * The class name and namespace of the base connection are identical in hyperf/database-pgsql
 * and menumbing/database-pgsql, so this subclass works with either of them.
 */
class PostgreSqlSwooleExtConnection extends BasePostgreSqlSwooleExtConnection
{
    use DetectsLostPgSqlConnections;

    protected function causedByLostConnection(Throwable $e): bool
    {
        return parent::causedByLostConnection($e) || $this->causedByLostPgSqlConnection($e);
    }

    /**
     * Same implementation as the parent, except that a falsy return value from
     * Swoole\Coroutine\PostgreSQL::prepare() is turned into an actionable message.
     *
     * Swoole returns false without touching $pdo->error on two paths, both of which mean the
     * underlying PGconn is unusable: the PQsetnonblocking guard and a failed PQsendPrepare while
     * the connection is already in non-blocking mode. The parent wraps that in an Exception with
     * an empty message, which no lost-connection detector can ever match.
     *
     * The connection name is passed along exactly like the parent does. It is the fourth
     * QueryException argument, which only exists since hyperf/database 3.2; older releases simply
     * ignore the extra argument, so this stays correct on both.
     */
    protected function prepare(string $query, bool $useReadPdo = true): PostgreSQLStatement
    {
        $num = 1;
        while (strpos($query, '?')) {
            $query = $this->strReplaceOnce('?', '$' . $num++, $query);
        }

        /** @var PostgreSQL $pdo */
        $pdo = $this->getPdoForSelect($useReadPdo);
        $statement = $pdo->prepare($query);
        if (! $statement) {
            throw new QueryException($query, [], new Exception($this->resolvePrepareFailureMessage($pdo)), $this->getName());
        }

        return $statement;
    }

    /**
     * Recover the real libpq message that Swoole swallowed, without ever claiming a lost
     * connection for a plain SQL error such as a syntax mistake.
     */
    protected function resolvePrepareFailureMessage(PostgreSQL $pdo): string
    {
        $message = $this->readSwoolePgSqlError($pdo->error ?? null);
        if ($message !== null) {
            return $message;
        }

        // PostgreSQL::query() is the only request path that has no PQsetnonblocking guard and that
        // always assigns $pdo->error from PQerrorMessage(), which makes it a safe probe. It costs
        // one round-trip, and only ever runs after prepare() has already failed. The @ is required
        // because Hyperf\ExceptionHandler\Listener\ErrorExceptionHandler turns every notice raised
        // by Swoole into an ErrorException.
        if (@$pdo->query('SELECT 1') !== false) {
            // The connection is alive, so the failure belongs to the statement itself.
            return 'Swoole\Coroutine\PostgreSQL::prepare() returned false without an error message.';
        }

        return $this->readSwoolePgSqlError($pdo->error ?? null)
            ?? 'Lost connection to PostgreSQL server while preparing statement.';
    }

    protected function readSwoolePgSqlError(mixed $error): ?string
    {
        if (! is_string($error)) {
            return null;
        }

        $error = trim($error);

        return $error === '' ? null : $error;
    }
}
