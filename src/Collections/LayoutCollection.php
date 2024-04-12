<?php

declare(strict_types=1);

namespace MoonShine\Layouts\Collections;

use Illuminate\Support\Collection;
use MoonShine\Layouts\Contracts\LayoutContract;

/**
 * @extends Collection<int, LayoutContract>
 */
final class LayoutCollection extends Collection
{
    public function findByKey(int $key, ?LayoutContract $default = null): ?LayoutContract
    {
        return $this->first(fn(LayoutContract $layout) => $layout->key() === $key, $default);
    }

    public function findByName(string $name, ?LayoutContract $default = null): ?LayoutContract
    {
        return $this->first(fn(LayoutContract $layout) => $layout->name() === $name, $default);
    }
}
