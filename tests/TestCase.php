<?php

namespace MoonShine\Layouts\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use MoonShine\Layouts\Providers\MoonShineLayoutsServiceProvider;
use MoonShine\Layouts\Tests\Fixtures\TestResource;
use MoonShine\Menu\MenuItem;
use MoonShine\Models\MoonshineUser;
use MoonShine\Models\MoonshineUserRole;
use MoonShine\Providers\MoonShineServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected MoonshineUser $adminUser;

    protected TestResource $resource;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('optimize:clear');

        $this->adminUser = MoonshineUser::factory()
            ->create($this->superAdminAttributes())
            ->load('moonshineUserRole');

        $this->resource = new TestResource();

        $this->loadMigrationsFrom(__DIR__ . '/Fixtures/Migrations');

        moonshine()->resources([
            $this->resource,
        ], true)->menu([
            MenuItem::make('Test', $this->resource),
        ]);
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.debug', 'true');
        $app['config']->set('moonshine.cache', 'array');
    }

    protected function getPackageProviders($app): array
    {
        return [
            MoonShineServiceProvider::class,
            MoonShineLayoutsServiceProvider::class,
        ];
    }

    protected function superAdminAttributes(): array
    {
        return [
            'id' => 1,
            'moonshine_user_role_id' => MoonshineUserRole::DEFAULT_ROLE_ID,
            'name' => fake()->name(),
            'email' => fake()->email(),
            'password' => bcrypt($this->superAdminPassword()),
        ];
    }

    protected function superAdminPassword(): string
    {
        return 'test';
    }
}
