<?php

declare(strict_types=1);

namespace MoonShine\Layouts\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use MoonShine\Layouts\Casts\LayoutsCast;

final class TestModel extends Model
{
    protected $fillable = [
        'data',
    ];

    protected $casts = [
        'data' => LayoutsCast::class,
    ];
}
