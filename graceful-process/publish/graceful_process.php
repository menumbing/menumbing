<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    /*
     * Safety-net timeout in seconds for the overall shutdown.
     *
     * Swoole's C-level max_wait_time is set to this value. If any process
     * is stuck, Swoole force-kills it after this duration.
     *
     * In normal operation, the package sends SIGINT well before this
     * timeout expires, so the actual shutdown is much faster.
     *
     * Docker's stop_grace_period (or Kubernetes terminationGracePeriodSeconds)
     * must be >= this value.
     *
     * Default: 300 seconds (5 minutes)
     */
    'timeout' => (int) env('GRACEFUL_PROCESS_TIMEOUT', 300),

    /*
     * Maximum time in seconds that HTTP workers wait for in-flight
     * requests to complete before force-stopping.
     *
     * After SIGTERM/SIGINT, each worker rejects new requests and monitors
     * active connections. In SWOOLE_BASE mode, workers close their
     * listening socket (new connections get "connection refused" at TCP
     * level). In SWOOLE_PROCESS mode, new requests get 503 via middleware.
     * The worker exits as soon as all in-flight requests finish. This
     * value is only a safety cap — if a request is stuck or takes too
     * long, the worker will be force-stopped after this duration.
     *
     * Set this to at least your longest expected HTTP request duration.
     *
     * Defaults to 'timeout' if not set.
     */
    'max_wait_time' => (int) env('GRACEFUL_PROCESS_MAX_WAIT_TIME', 30),
];
