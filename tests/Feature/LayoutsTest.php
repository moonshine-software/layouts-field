<?php

declare(strict_types=1);

namespace MoonShine\Layouts\Tests\Feature;

use Illuminate\Http\UploadedFile;
use MoonShine\Layouts\Tests\Fixtures\TestModel;
use MoonShine\Layouts\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class LayoutsTest extends TestCase
{
    #[Test]
    public function it_successful_save(): void
    {
        $model = TestModel::query()->create();

        $this->actingAs($this->adminUser, 'moonshine')
            ->put($this->resource->route('crud.update', $model))
            ->assertRedirect();

        $image = UploadedFile::fake()->image('image.jpg');

        $data = [
            'id' => $model->id,
            'data' => [
                ['_layout' => 'first', 'title' => 'First title', 'image' => $image],
                ['_layout' => 'second', 'title' => 'Second title', 'images' => [$image]],
            ]
        ];

        $this->actingAs($this->adminUser, 'moonshine')
            ->put($this->resource->route('crud.update', $model), $data)
            ->assertRedirect();

        $model->refresh();

        $first = $model->data->findByName('first');
        $second = $model->data->findByName('second');

        $this->assertEquals('First title', $first->get('title'));
        $this->assertEquals('Second title', $second->get('title'));
        $this->assertEquals($image->hashName(), $first->get('image'));
        $this->assertEquals([$image->hashName()], $second->get('images'));

        $data = [
            'id' => $model->id,
            'data' => [
                ['_layout' => 'first', 'title' => 'First title', 'hidden_image' => $image->hashName()],
                ['_layout' => 'second', 'title' => 'Second title', 'hidden_images' => [$image->hashName()]],
            ]
        ];

        $this->actingAs($this->adminUser, 'moonshine')
            ->put($this->resource->route('crud.update', $model), $data)
            ->assertRedirect();

        $model->refresh();

        $first = $model->data->findByName('first');
        $second = $model->data->findByName('second');

        $this->assertEquals($image->hashName(), $first->get('image'));
        $this->assertEquals([$image->hashName()], $second->get('images'));

        $data = [
            'id' => $model->id,
            'data' => [
                ['_layout' => 'first', 'title' => 'First title'],
                ['_layout' => 'second', 'title' => 'Second title'],
            ]
        ];

        $this->actingAs($this->adminUser, 'moonshine')
            ->put($this->resource->route('crud.update', $model), $data)
            ->assertRedirect();

        $model->refresh();

        $first = $model->data->findByName('first');
        $second = $model->data->findByName('second');

        $this->assertEquals(null, $first->get('image'));
        $this->assertEquals([], $second->get('images'));
    }
}
