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
namespace HyperfTest\Database\Resilience\Stubs;

use Hyperf\Database\DetectsLostConnections;
use Menumbing\Database\Resilience\Concerns\DetectsLostPgSqlConnections;
use Throwable;

/**
 * Mirrors what Menumbing\Database\Resilience\PostgreSqlConnection does, but on top of
 * Hyperf\Database\DetectsLostConnections directly, so the detection rules can be exercised
 * without hyperf/database-pgsql being installed.
 *
 * The alias keeps the upstream implementation reachable, exactly like parent::causedByLostConnection()
 * does in the real subclasses.
 */
class LostConnectionDetector
{
    use DetectsLostConnections {
        causedByLostConnection as causedByLostConnectionByHyperf;
    }
    use DetectsLostPgSqlConnections;

    public function causedByLostConnection(Throwable $e): bool
    {
        return $this->causedByLostConnectionByHyperf($e) || $this->causedByLostPgSqlConnection($e);
    }

    /**
     * @return string[]
     */
    public function pgSqlLostConnectionMessages(): array
    {
        return $this->pgsqlLostConnectionMessages;
    }
}
