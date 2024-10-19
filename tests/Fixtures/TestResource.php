<?php

declare(strict_types=1);

namespace MoonShine\Layouts\Tests\Fixtures;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Layouts\Fields\Layouts;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Text;

final class TestResource extends ModelResource
{
    protected string $model = TestModel::class;

    protected function indexFields(): iterable
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
                    ]),
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

    protected function formFields(): iterable
    {
        return $this->indexFields();
    }

    protected function detailFields(): iterable
    {
        return $this->indexFields();
    }
}
