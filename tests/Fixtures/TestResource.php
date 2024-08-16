<?php

declare(strict_types=1);

namespace MoonShine\Layouts\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use MoonShine\Fields\ID;
use MoonShine\Fields\Image;
use MoonShine\Fields\Text;
use MoonShine\Layouts\Fields\Layouts;
use MoonShine\Resources\ModelResource;

final class TestResource extends ModelResource
{
    protected string $model = TestModel::class;

    public function fields(): array
    {
        return [
            ID::make(),
            Layouts::make('Data')->addLayout('first', 'first', [
                Text::make('Title'),
                Image::make('Image')->removable(),
            ])->addLayout('second', 'second', [
                Text::make('Title'),
                Image::make('Images')->multiple()->removable(),
            ])
        ];
    }

    public function rules(Model $item): array
    {
        return [];
    }
}
