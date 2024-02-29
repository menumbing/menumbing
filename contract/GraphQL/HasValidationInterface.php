<?php

declare(strict_types=1);

namespace Menumbing\Contract\GraphQL;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface HasValidationInterface
{
    public function validationRules(): array;

    public function validationMessages(): array;
}
