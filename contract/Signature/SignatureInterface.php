<?php

declare(strict_types=1);

namespace Menumbing\Contract\Signature;

use Psr\Http\Message\ServerRequestInterface;
use Stringable;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
interface SignatureInterface extends Stringable
{
    public function getHeaders(): array;

    public function isValid(ServerRequestInterface $request): bool;

    public function extractSignature(ServerRequestInterface $request): string;
}
