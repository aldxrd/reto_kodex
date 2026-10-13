<?php

declare(strict_types=1);

namespace Kodex\Http;

use Closure;
use Kodex\Domain\Role;

final class Route
{
    public readonly Closure $handler;

    /** @param list<Role>|null $roles */
    public function __construct(
        public readonly string $method,
        public readonly string $pattern,
        public readonly string $regex,
        callable $handler,
        public readonly bool $public,
        public readonly ?array $roles,
    ) {
        $this->handler = Closure::fromCallable($handler);
    }
}
