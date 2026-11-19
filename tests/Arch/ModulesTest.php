<?php

/*
|--------------------------------------------------------------------------
| Architecture rules — docs/spec/07-arquitectura.md §4
|--------------------------------------------------------------------------
|
| These tests keep the modular monolith honest. If one fails, fix the code,
| not the test; changing a rule requires updating the spec first.
|
*/

arch('no debugging helpers are left in the code')
    ->preset()
    ->php();

arch('Shared does not depend on any business module')
    ->expect('App\Modules\Shared')
    ->not->toUse(array_map(fn (string $module): string => "App\\Modules\\{$module}", businessModules()));

foreach (businessModules() as $module) {
    $namespace = "App\\Modules\\{$module}";

    foreach (['Http', 'Jobs', 'Listeners', 'Policies'] as $layer) {
        arch("{$module}: {$layer} is internal to the module")
            ->expect("{$namespace}\\{$layer}")
            ->toOnlyBeUsedIn($namespace);
    }

    arch("{$module}: controllers are thin (no transactions or queries builders)")
        ->expect("{$namespace}\\Http\\Controllers")
        ->toHaveSuffix('Controller')
        ->not->toUse(['Illuminate\Support\Facades\DB']);

    arch("{$module}: actions expose handle() and know nothing about HTTP")
        ->expect("{$namespace}\\Actions")
        ->toBeClasses()
        ->toHaveMethod('handle')
        ->not->toUse(['Illuminate\Http\Request', 'Inertia\Inertia']);

    arch("{$module}: enums live in Enums")
        ->expect("{$namespace}\\Enums")
        ->toBeEnums();

    arch("{$module}: models are Eloquent models")
        ->expect("{$namespace}\\Models")
        ->toExtend('Illuminate\Database\Eloquent\Model');

    arch("{$module}: contracts are interfaces")
        ->expect("{$namespace}\\Contracts")
        ->toBeInterfaces();
}
