<?php

declare(strict_types=1);

namespace MoonShine\Layouts\Tests\Feature;

use Illuminate\Http\UploadedFile;
use MoonShine\Layouts\Casts\LayoutItem;
use MoonShine\Layouts\Tests\Fixtures\TestModel;
use MoonShine\Layouts\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class LayoutsTest extends TestCase
{
    private function store(TestModel $model, array $data = []): void
    {
        $this->actingAs($this->adminUser, 'moonshine')
            ->put($this->resource->getRoute('crud.update', $model), $data)
            ->assertRedirect();

        $model->refresh();
    }

    #[Test]
    public function it_simple_create(): void
    {
        $image = UploadedFile::fake()->image('image.jpg');

        $data = [
            'data' => [
                ['_layout' => 'first', 'title' => 'First title', 'image' => $image, 'json' => [
                    ['key' => 'key 1', 'value' => 'value 1'],
                    ['key' => 'key 2', 'value' => 'value 2'],
                ]],
                ['_layout' => 'second', 'title' => 'Second title', 'images' => [$image], 'json' => [
                    ['title' => 'Title 1', 'image' => $image],
                ]],
            ],
        ];

        $this->actingAs($this->adminUser, 'moonshine')
            ->post($this->resource->getRoute('crud.store'), $data)
            ->assertRedirect();

        $model = TestModel::query()->first();

        $first = static fn (TestModel $model): array => $model->data->findByName('first')->get('json');
        $second = static fn (TestModel $model): array => $model->data->findByName('second')->get('json');

        $this->assertEquals(['key 1' => 'value 1', 'key 2' => 'value 2'], $first($model));
        $this->assertEquals([['title' => 'Title 1', 'image' => $image->hashName()]], $second($model));
    }

    #[Test]
    public function it_json_with_image(): void
    {
        $model = TestModel::query()->create();

        $image = UploadedFile::fake()->image('image.jpg');

        // simple store
        $data = [
            'data' => [
                ['_layout' => 'first', 'title' => 'First title', 'image' => $image, 'json' => [
                    ['key' => 'key 1', 'value' => 'value 1'],
                    ['key' => 'key 2', 'value' => 'value 2'],
                ]],
                ['_layout' => 'second', 'title' => 'Second title', 'images' => [$image], 'json' => [
                    ['title' => 'Title 1', 'image' => $image],
                ]],
            ],
        ];

        $this->store($model, $data);

        $first = static fn (TestModel $model): array => $model->data->findByName('first')->get('json');
        $second = static fn (TestModel $model): array => $model->data->findByName('second')->get('json');

        $this->assertEquals(['key 1' => 'value 1', 'key 2' => 'value 2'], $first($model));
        $this->assertEquals([['title' => 'Title 1', 'image' => $image->hashName()]], $second($model));

        // stay images
        $data = [
            'data' => [
                ['_layout' => 'first', 'title' => 'First title'],
                ['_layout' => 'second', 'title' => 'Second title', 'json' => [
                    ['title' => 'Title 1', 'hidden_image' => $image->hashName()],
                ]],
            ],
        ];

        $this->store($model, $data);

        $this->assertEquals([['title' => 'Title 1', 'image' => $image->hashName()]], $second($model));

        // remove images
        $data = [
            'id' => $model->id,
            'data' => [
                ['_layout' => 'first', 'title' => 'First title'],
                ['_layout' => 'second', 'title' => 'Second title', 'json' => [
                    ['title' => 'Title 1'],
                ]],
            ],
        ];

        $this->store($model, $data);

        $this->assertEquals([['title' => 'Title 1', 'image' => null]], $second($model));
    }

    #[Test]
    public function it_with_image(): void
    {
        $model = TestModel::query()->create();
        $image = UploadedFile::fake()->image('image.jpg');

        // simple store
        $data = [
            'data' => [
                ['_layout' => 'first', 'title' => 'First title', 'image' => $image],
                ['_layout' => 'second', 'title' => 'Second title', 'images' => [$image]],
            ],
        ];

        $this->store($model, $data);

        $first = static fn (TestModel $model): LayoutItem => $model->data->findByName('first');
        $second = static fn (TestModel $model): LayoutItem => $model->data->findByName('second');

        $this->assertEquals('First title', $first($model)->get('title'));
        $this->assertEquals('Second title', $second($model)->get('title'));
        $this->assertEquals($image->hashName(), $first($model)->get('image'));
        $this->assertEquals([$image->hashName()], $second($model)->get('images'));

        // stay images
        $data = [
            'data' => [
                ['_layout' => 'first', 'title' => 'First title', 'hidden_image' => $image->hashName()],
                ['_layout' => 'second', 'title' => 'Second title', 'hidden_images' => [$image->hashName()]],
            ],
        ];

        $this->store($model, $data);

        $this->assertEquals($image->hashName(), $first($model)->get('image'));
        $this->assertEquals([$image->hashName()], $second($model)->get('images'));

        // remove images
        $data = [
            'data' => [
                ['_layout' => 'first', 'title' => 'First title'],
                ['_layout' => 'second', 'title' => 'Second title'],
            ],
        ];

        $this->store($model, $data);

        $this->assertEquals(null, $first($model)->get('image'));
        $this->assertEquals([], $second($model)->get('images'));
    }
}
