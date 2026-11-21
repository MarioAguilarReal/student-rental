<?php

use App\Models\User;
use App\Modules\Notifications\Messages\PlatformMessage;

/**
 * HU-10.02 · Centro de notificaciones.
 */
function notifyUser(User $user, string $url = '/rentals'): void
{
    $user->notify(new PlatformMessage('rental.approved', 'Your request for :property was approved', 'Pay the first charge.', 'View rental', $url, ['property' => 'Casa Centro']));
}

it('lists the user\'s own notifications, newest first, with the unread count', function () {
    $client = verifiedClient();
    notifyUser($client);
    notifyUser($client);
    notifyUser(verifiedClient());

    $this->actingAs($client)->get(route('notifications.index'))
        ->assertInertia(fn ($page) => $page
            ->component('notifications/index')
            ->has('notifications.data', 2)
            ->where('notifications.data.0.title', 'Tu solicitud para Casa Centro fue aprobada')
            ->where('unreadCount', 2)
            ->where('auth.unreadNotifications', 2));
});

it('opens a notification: marks it read and goes where the person acts', function () {
    $client = verifiedClient();
    notifyUser($client, '/rentals/5');
    $notification = $client->notifications()->sole();

    $this->actingAs($client)->post(route('notifications.open', $notification->id))->assertRedirect('/rentals/5');

    expect($notification->fresh()?->read_at)->not->toBeNull();
});

it('never follows a link outside the platform', function () {
    $client = verifiedClient();
    notifyUser($client, 'https://example.com/phishing');

    $this->actingAs($client)->post(route('notifications.open', $client->notifications()->sole()->id))
        ->assertRedirect(route('notifications.index'));
});

it('does not open someone else\'s notification', function () {
    $other = verifiedClient();
    notifyUser($other);

    $this->actingAs(verifiedClient())->post(route('notifications.open', $other->notifications()->sole()->id))->assertNotFound();
});

it('marks everything as read and filters unread', function () {
    $client = verifiedClient();
    notifyUser($client);
    notifyUser($client);

    $this->actingAs($client)->post(route('notifications.read-all'))->assertRedirect();

    expect($client->unreadNotifications()->count())->toBe(0);
    $this->actingAs($client)->get(route('notifications.index', ['unread' => 1]))
        ->assertInertia(fn ($page) => $page->has('notifications.data', 0)->where('totalCount', 2));
});
