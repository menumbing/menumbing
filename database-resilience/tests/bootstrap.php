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

$packageVendor = dirname(__DIR__) . '/vendor/autoload.php';
$monorepoVendor = dirname(__DIR__, 2) . '/vendor/autoload.php';

if (file_exists($packageVendor)) {
    require_once $packageVendor;
} elseif (file_exists($monorepoVendor)) {
    // Running from inside the menumbing monorepo, where dependencies live in the root vendor.
    require_once $monorepoVendor;
} else {
    fwrite(STDERR, 'Run `composer install` before executing the test suite.' . PHP_EOL);
    exit(1);
}

// The root vendor of the monorepo knows nothing about this package's autoload-dev section, so the
// test doubles have to be resolvable on their own when the suite runs from the monorepo root.
spl_autoload_register(static function (string $class): void {
    $prefix = 'HyperfTest\\Database\\Resilience\\';

    if (! str_starts_with($class, $prefix)) {
        return;
    }

    $path = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

    if (is_file($path)) {
        require_once $path;
    }
});

date_default_timezone_set('UTC');
