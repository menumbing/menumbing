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

use Hyperf\Database\PgSQL\PostgreSqlConnection as BasePostgreSqlConnection;
use Menumbing\Database\Resilience\Concerns\DetectsLostPgSqlConnections;
use Throwable;

/**
 * Drop-in replacement for Hyperf\Database\PgSQL\PostgreSqlConnection (the pdo_pgsql driver).
 *
 * Swoole hooks pdo_pgsql and registers its own driver whose pdo_pgsql_check_liveness() already
 * calls PQreset(), so this driver reports the real libpq messages. Only the server side messages
 * that Hyperf\Database\DetectsLostConnections is missing need to be added here.
 */
class PostgreSqlConnection extends BasePostgreSqlConnection
{
    use DetectsLostPgSqlConnections;

    protected function causedByLostConnection(Throwable $e): bool
    {
        return parent::causedByLostConnection($e) || $this->causedByLostPgSqlConnection($e);
    }
}
