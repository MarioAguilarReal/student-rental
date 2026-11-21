<?php

use App\Modules\Identity\Enums\Role;
use Illuminate\Support\Facades\Route;

/**
 * HU-11.01 · Bitácora (RN-90).
 */
beforeEach(function () {
    $this->admin = userWithRole(Role::Admin);
    $this->actor = userWithRole(Role::SuperAdmin, ['first_name' => 'Lucía', 'last_name' => 'Ramos']);

    activity('rental')->causedBy($this->actor)->event('approved')->log('Rental approved');
    activity('payment')->causedByAnonymous()->event('overdue')->log('Charge overdue');
    $this->travel(-10)->days();
    activity('rental')->causedByAnonymous()->event('requested')->log('Rental requested');
    $this->travelBack();
});

it('shows who changed what and when, newest first', function () {
    $this->actingAs($this->admin)->get(route('audit.index'))
        ->assertInertia(fn ($page) => $page
            ->component('audit/index')
            ->has('records.data', 3)
            ->where('records.data.0.log_label', 'Rentas')
            ->where('records.data.1.description', 'Cargo vencido'));
});

it('filters by area, actor and dates', function (array $filters, int $count) {
    $this->actingAs($this->admin)->get(route('audit.index', $filters))
        ->assertInertia(fn ($page) => $page->has('records.data', $count));
})->with([
    'area' => [['log' => 'payment'], 1],
    'actor by name' => [['actor' => 'lucía'], 1],
    'dates' => [fn () => ['from' => now()->subDays(3)->toDateString(), 'to' => now()->toDateString()], 2],
]);

it('rejects a date range that ends before it starts', function () {
    $this->actingAs($this->admin)->get(route('audit.index', ['from' => '2026-11-10', 'to' => '2026-11-01']))
        ->assertSessionHasErrors('to');
});

it('is read only and for administrators', function () {
    $this->actingAs(verifiedOwner())->get(route('audit.index'))->assertForbidden();

    $methods = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with($route->uri(), 'admin/audit'))
        ->flatMap(fn ($route) => $route->methods())
        ->unique()->values()->all();

    expect($methods)->toEqualCanonicalizing(['GET', 'HEAD']);
});
