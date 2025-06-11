<?php

declare(strict_types=1);

namespace Menumbing\Contract\EventStream;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class StreamMessage
{
    public function __construct(
        public readonly string $stream,
        public readonly string $type,
        public readonly mixed $data,
        public readonly array $context = [],
        public readonly ?string $id = null,
    ) {
    }

    public function withId(string $id): static
    {
        return new static($this->stream, $this->type, $this->data, $this->context, $id);
    }

    public function withContext(array $context): static
    {
        return new static($this->stream, $this->type, $this->data, [...$this->context, ...$context]);
    }
}
