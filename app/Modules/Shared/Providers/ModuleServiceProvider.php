<?php

namespace App\Modules\Shared\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;

/**
 * Base provider for every module in app/Modules. A module registers here its
 * routes (routes.php next to the provider), policies, event listeners and scheduled tasks.
 * See docs/spec/07-arquitectura.md §3.
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string> model => policy
     */
    protected array $policies = [];

    /**
     * @var array<class-string, list<class-string|array{class-string, string}>> event => listeners (class or [class, method])
     */
    protected array $listen = [];

    public function boot(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        foreach ($this->listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }

        $routes = $this->modulePath('routes.php');

        if (file_exists($routes) && ! $this->app->routesAreCached()) {
            Route::middleware('web')->group($routes);
        }

        $this->callAfterResolving(Schedule::class, fn (Schedule $schedule) => $this->schedule($schedule));
    }

    /**
     * Override to register the module's scheduled tasks (expirations, due dates…).
     */
    protected function schedule(Schedule $schedule): void {}

    protected function modulePath(string $path = ''): string
    {
        $directory = dirname((string) (new ReflectionClass(static::class))->getFileName());

        return $path === '' ? $directory : $directory.DIRECTORY_SEPARATOR.$path;
    }
}
