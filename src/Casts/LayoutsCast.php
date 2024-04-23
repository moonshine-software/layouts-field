<?php

namespace MoonShine\Layouts\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use JsonException;
use MoonShine\Layouts\Collections\LayoutItemCollection;
use Throwable;

class LayoutsCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     * @throws JsonException
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if($value instanceof LayoutItemCollection) {
            return $value;
        }

        return $this->_map($value);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     * @throws JsonException
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): LayoutItemCollection
    {
        if(!$value instanceof LayoutItemCollection) {
            return $this->_map($value);
        }

        return $value;
    }

    private function _map(mixed $value): LayoutItemCollection
    {
        $values = [];

        try {
            if(is_string($value) && str($value)->isJson()) {
                $value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            }

            $values = collect($value)->map(function (array $data) {
                return new LayoutItem(
                    $data['name'],
                    $data['key'] ?? 0,
                    $data['values'] ?? [],
                );
            })->filter();
        } catch (Throwable) {}

        return LayoutItemCollection::make($values);
    }
}
