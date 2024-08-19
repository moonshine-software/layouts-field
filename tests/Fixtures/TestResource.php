<?php

declare(strict_types=1);

namespace MoonShine\Layouts\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use MoonShine\Decorations\Column;
use MoonShine\Decorations\Grid;
use MoonShine\Fields\ID;
use MoonShine\Fields\Image;
use MoonShine\Fields\Json;
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
                Grid::make([
                    Column::make([
                        Text::make('Title'),
                    ]),
                    Column::make([
                        Image::make('Image')->removable(),
                    ])
                ]),
                Json::make('Json')->keyValue(),
            ])->addLayout('second', 'second', [
                Text::make('Title'),
                Image::make('Images')->multiple()->removable(),
                Json::make('Json')->fields([
                    Text::make('Title'),
                    Image::make('Image')->removable(),
                ]),
            ]),
        ];
    }

    public function rules(Model $item): array
    {
        return [];
    }
}
