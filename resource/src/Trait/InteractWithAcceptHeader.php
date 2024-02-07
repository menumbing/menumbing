<?php

declare(strict_types=1);

namespace Menumbing\Resource\Trait;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Stringable\Str;
use Symfony\Component\HttpFoundation\AcceptHeader;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
trait InteractWithAcceptHeader
{
    protected function wantsJson(RequestInterface $request): bool
    {
        return $this->wantsTo($request, ['/json', '+json']);
    }

    protected function wantsTo(RequestInterface $request, array $needles): bool
    {
        $acceptable = $this->getContentTypes($request);

        return isset($acceptable[0]) && Str::contains(strtolower($acceptable[0]), $needles);
    }

    protected function getContentTypes(RequestInterface $request): array
    {
        return array_keys(AcceptHeader::fromString($request->header('Accept'))->all());
    }
}
