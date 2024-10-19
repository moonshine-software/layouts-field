<?php

namespace MoonShine\Layouts\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Layouts\Collections\LayoutItemCollection;
use Throwable;

class LayoutsCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?LayoutItemCollection
    {
        if (! isset($attributes[$key])) {
            return null;
        }

        $data = Json::decode($attributes[$key]);

        if ($data instanceof LayoutItemCollection) {
            return $data;
        }

        return is_array($data) ? $this->_map($data) : null;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        return [$key => Json::encode($value)];
    }

    private function _map(mixed $value): LayoutItemCollection
    {
        $values = [];

        try {
            if (is_string($value) && str($value)->isJson()) {
                $value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            }

            $values = collect($value)->map(fn (array $data): LayoutItem => new LayoutItem(
                $data['name'],
                $data['key'] ?? 0,
                $data['values'] ?? [],
            ))->filter();
        } catch (Throwable) {
        }

        return LayoutItemCollection::make($values);
    }
}
