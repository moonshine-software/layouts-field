<?php

declare(strict_types=1);

namespace MoonShine\Layouts\Casts;

use JsonSerializable;

final class LayoutItem implements JsonSerializable
{
    public function __construct(
        private string $name,
        private int $key = 0,
        private array $values = []
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getKey(): int
    {
        return $this->key;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->values, $key, $default);
    }

    public function jsonSerialize(): array
    {
        return [
            'key' => $this->getKey(),
            'name' => $this->getName(),
            'values' => $this->getValues(),
        ];
    }
}
