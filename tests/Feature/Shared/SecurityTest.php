<?php

use App\Modules\Identity\Actions\SuspendUser;
use App\Modules\Identity\Enums\Role;
use Illuminate\Support\Facades\Notification;

/**
 * Iteración 8 · Revisión de seguridad.
 */
it('sends the security headers on every page', function () {
    $this->get(route('home'))
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

it('limits how often a client can send rental requests', function () {
    Notification::fake();
    $client = verifiedClient();
    $property = listedProperty();

    foreach (range(1, 10) as $attempt) {
        $this->actingAs($client)->post(route('rentals.store', $property), rentalRequest($property, ['accept_rules' => false]));
    }

    $this->actingAs($client)->post(route('rentals.store', $property), rentalRequest($property))->assertTooManyRequests();
});

it('signs a suspended user out on their next request (HU-01.04)', function () {
    $client = verifiedClient();
    app(SuspendUser::class)->handle(userWithRole(Role::Admin), $client, 'Comportamiento indebido.');

    $this->actingAs($client->fresh() ?? $client)->get(route('dashboard'))->assertRedirect();
    $this->assertGuest();
});
