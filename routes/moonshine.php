<?php

use Illuminate\Support\Facades\Route;
use MoonShine\Layouts\Http\Controllers\LayoutsController;

Route::group(moonshine()->configureRoutes(), static function (): void {
    Route::post('/layouts/{pageUri}/{resourceUri?}', [LayoutsController::class, 'store'])
        ->name('layouts-field.store');
});

