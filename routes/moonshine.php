<?php

use Illuminate\Support\Facades\Route;
use MoonShine\Layouts\Http\Controllers\LayoutsController;

Route::moonshine(static function (): void {
    Route::post('/layouts/{resourceUri?}', [LayoutsController::class, 'store'])
        ->name('layouts-field.store');
}, withPage: true, withAuthenticate: true);

