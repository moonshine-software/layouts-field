<?php

declare(strict_types=1);

namespace MoonShine\Layouts\Collections;

use Illuminate\Support\Collection;
use MoonShine\Layouts\Casts\LayoutItem;

/**
 * @extends Collection<int, LayoutItem>
 */
final class LayoutItemCollection extends Collection
{
    public function findByName(string $value): ?LayoutItem
    {
        return $this->firstWhere(fn(LayoutItem $item): bool => $item->getName() === $value);
    }

    public function findByKey(int $value): ?LayoutItem
    {
        return $this->firstWhere(fn(LayoutItem $item): bool => $item->getKey() === $value);
    }
}
