<?php

declare(strict_types=1);

namespace MoonShine\Layouts\Providers;

use Illuminate\Support\ServiceProvider;

final class MoonShineLayoutsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'moonshine-layouts-field');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/moonshine.php');

        $this->publishes([
            __DIR__ . '/../../public' => public_path('vendor/moonshine-layouts-field'),
        ], ['moonshine-layouts-field', 'laravel-assets']);
    }
}
