# Database Resilience for Hyperf

Keeps [Hyperf](https://hyperf.io) database connections usable after the server drops them.

The first layer it ships is lost connection detection for PostgreSQL, covering both the `pgsql`
(pdo_pgsql) and `pgsql-swoole` drivers.

Hyperf already reconnects when a query fails with a message it recognises, through
`Hyperf\Database\DetectsLostConnections`. The PostgreSQL drivers produce a different set of
messages, and the Swoole driver hides some of them entirely, so a dead connection is reported to
the application as a hard `QueryException` instead of being retried. This package adds the missing
messages without patching or replacing any Hyperf class.

## Features

- **Lost connection detection** — recognises the libpq, Swoole and PostgreSQL server messages that
  `Hyperf\Database\DetectsLostConnections` does not cover (restarts, failovers, recovery conflicts,
  abandoned non-blocking requests)
- **Silent prepare failures surfaced** — `Swoole\Coroutine\PostgreSQL::prepare()` can return `false`
  without setting an error, which the upstream driver turns into an exception with an empty message;
  this package recovers the real libpq message so the failure is both actionable and detectable
- **Zero configuration** — no config file to publish, the drivers keep their existing names
- **Additive, not destructive** — every upstream message is still honoured; new messages are only
  checked after `parent::causedByLostConnection()` returns `false`
- **Load-order independent** — the factory binding uses a `PriorityDefinition`, so it wins over
  `hyperf/db-connection` no matter which order Composer reports the packages in
- **Stays out of the way** — when the PostgreSQL component is not installed, when Swoole is built
  without `--enable-swoole-pgsql`, or when the application registered its own connection resolver,
  nothing is replaced
- **Works with either PostgreSQL component** — the class and namespace names are identical in
  `hyperf/database-pgsql` and `menumbing/database-pgsql`

## Requirements

- PHP >= 8.1
- Hyperf >= 3.1
- `hyperf/database-pgsql` (or `menumbing/database-pgsql`)
- Swoole built with `--enable-swoole-pgsql`, only for the `pgsql-swoole` driver

## Installation

```bash
composer require menumbing/database-resilience
```

The `ConfigProvider` is registered automatically through `composer.json` extra, there is nothing to
publish and nothing to add to `config/autoload/dependencies.php`.

## Configuration

Use the standard PostgreSQL drivers, exactly as before:

```php
// config/autoload/databases.php

return [
    'default' => [
        'driver' => env('DB_DRIVER', 'pgsql'),
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', 5432),
        'database' => env('DB_DATABASE', 'forge'),
        'username' => env('DB_USERNAME', 'forge'),
        'password' => env('DB_PASSWORD', ''),
        'pool' => [
            'min_connections' => 1,
            'max_connections' => 10,
            'max_idle_time' => 60,
        ],
    ],
];
```

Set `'driver' => 'pgsql-swoole'` to use the Swoole PostgreSQL client instead of pdo_pgsql. Both
drivers are routed to the resilient connection classes by this package.

## How it works

### Connection factory binding

`hyperf/db-connection` binds `Hyperf\Database\Connectors\ConnectionFactory` to itself. This package
binds the same identifier to `Menumbing\Database\Resilience\Connectors\ConnectionFactory` through a
`Hyperf\Di\Definition\PriorityDefinition`, which is the only definition form that
`Hyperf\Config\ProviderConfig::merge()` never overwrites with a plain string. The binding therefore
wins regardless of package load order, and `Hyperf\Di\Definition\DefinitionSource` unwraps it back
into the class name.

`ConnectionFactory::createConnection()` is overridden rather than using `Connection::resolverFor()`
on purpose. The PostgreSQL component registers its own resolver from a `BootApplication` listener,
and listeners sharing the default priority are pulled out of `Hyperf\Event\ListenerProvider` in a
non-deterministic order. Overriding the factory does not depend on listener ordering at all, because
`Hyperf\Database\Connectors\ConnectionFactory::createConnection()` consults the resolver itself.

Only the exact classes shipped by the PostgreSQL component are replaced. An application that
registered its own resolver, or that subclasses the component connection, keeps its own class.

### Lost connection detection

`Menumbing\Database\Resilience\Concerns\DetectsLostPgSqlConnections` is mixed into
`Menumbing\Database\Resilience\PostgreSqlConnection` and
`Menumbing\Database\Resilience\PostgreSqlSwooleExtConnection`, which extend the corresponding classes from
the PostgreSQL component. Both keep calling `parent::causedByLostConnection()` first and only fall
back to `causedByLostPgSqlConnection()`, so any message Hyperf adds upstream is still honoured.

The trait deliberately does not override `causedByLostConnection()`, which keeps it safe to mix into
any `Hyperf\Database\Connection` subclass.

### Silent Swoole prepare failures

`Swoole\Coroutine\PostgreSQL::prepare()` guards with `PQsetnonblocking()`, which libpq only fails
when the connection status is already `CONNECTION_BAD`. Swoole raises a notice but never reads
`PQerrorMessage()`, so the real cause is swallowed and the upstream driver builds a `QueryException`
whose message is only `" (SQL: ...)"` — something no keyword list can ever match.

`Menumbing\Database\Resilience\PostgreSqlSwooleExtConnection::prepare()` reads `$pdo->error` first and,
when that is empty, probes the connection with `SELECT 1` to tell a dead connection apart from a
plain SQL error such as a syntax mistake.

## Testing

```bash
composer install
composer test
```

`require-dev` includes `hyperf/database-pgsql`, so the suite covers both drivers. It also runs from
inside the menumbing monorepo, where dependencies live in the root `vendor`; tests that need the
PostgreSQL component, or Swoole built with `--enable-swoole-pgsql`, are skipped when those are
absent instead of failing.

```bash
# from the monorepo root
vendor/bin/phpunit -c database-resilience/phpunit.xml
```

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
