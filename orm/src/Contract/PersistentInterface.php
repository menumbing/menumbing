<?php

declare(strict_types=1);

namespace Menumbing\Orm\Contract;

use Menumbing\Orm\Contract\PersistentMiddlewareInterface;
use Menumbing\Orm\Model;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface PersistentInterface
{
    public function addMiddleware(PersistentMiddlewareInterface $middleware): void;

    public function save(Model $model): Model;

    public function delete(Model $model): Model;
}
