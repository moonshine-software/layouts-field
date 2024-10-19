<?php

declare(strict_types=1);

namespace MoonShine\Layouts\Contracts;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Renderable;
use MoonShine\Laravel\Collections\Fields;
use Stringable;

interface LayoutContract extends Htmlable, Stringable, Renderable
{
    public function title(): string;

    public function key(): int;

    public function name(): string;

    public function fields(): Fields;
}
