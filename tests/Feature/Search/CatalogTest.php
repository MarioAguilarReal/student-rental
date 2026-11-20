<?php

use App\Modules\Catalog\Enums\AmenityScope;
use App\Modules\Catalog\Models\Amenity;
use App\Modules\Catalog\Models\Campus;
use App\Modules\Identity\Enums\Role;
use App\Modules\Pricing\Models\AvailabilityBlock;
use App\Modules\Properties\Enums\GenderPolicy;
use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Enums\UnitType;
use App\Modules\Search\Support\Geo;
use App\Modules\Verification\Enums\CommunityType;
use App\Modules\Verification\Enums\VerificationKind;
use App\Modules\Verification\Enums\VerificationStatus;

/**
 * HU-07.01 · Buscar unidades.
 */
function searchIds(array $query = [], $as = null): array
{
    $response = ($as ? test()->actingAs($as) : test())->get(route('home', $query))->assertOk();

    return collect($response->viewData('page')['props']['listings']['data'])->pluck('id')->all();
}

it('lists only published properties with a current inspection and a priced rentable space', function () {
    $listed = listedProperty();
    listedProperty(['status' => PropertyStatus::Paused]);
    listedProperty(['inspection_valid_until' => now()->subDay()]);
    listedProperty(unit: ['is_rentable' => false]);

    expect(searchIds())->toBe([$listed->id]);
});

it('is public and uses the catalog page component', function () {
    listedProperty();

    $this->get(route('home'))->assertInertia(fn ($page) => $page->component('stays/index')->has('listings.data', 1));
});

it('never exposes the exact location on the card', function () {
    listedProperty(['latitude' => 21.491234, 'longitude' => -104.894987]);

    $card = $this->get(route('home'))->viewData('page')['props']['listings']['data'][0];

    expect($card['lat'])->toBe(Geo::approximate(21.491234))
        ->and($card['lat'])->not->toBe(21.491234)
        ->and($card)->not->toHaveKey('street');
});

it('filters by distance to a campus and shows it', function () {
    $campus = Campus::factory()->create(['latitude' => 21.4920, 'longitude' => -104.8942]);
    $near = listedProperty(['latitude' => 21.4970, 'longitude' => -104.8992]);
    listedProperty(['latitude' => 21.6120, 'longitude' => -104.8942]); // ~13 km north

    $props = $this->get(route('home', ['campus' => $campus->id, 'radius' => 5]))->viewData('page')['props'];

    expect(collect($props['listings']['data'])->pluck('id')->all())->toBe([$near->id])
        ->and($props['listings']['data'][0]['distance_km'])->toBeLessThan(1.0);
});

it('filters by space type, price and gender policy', function () {
    $room = listedProperty(unit: ['type' => UnitType::PrivateRoom, 'name' => 'Habitación'], monthlyMinor: 350_000);
    $house = listedProperty(['gender_policy' => GenderPolicy::WomenOnly], monthlyMinor: 1_200_000);

    expect(searchIds(['type' => 'private_room']))->toBe([$room->id])
        ->and(searchIds(['max_price' => 5000]))->toBe([$room->id])
        ->and(searchIds(['min_price' => 5000]))->toBe([$house->id])
        ->and(searchIds(['gender' => 'women_only']))->toBe([$house->id]);
});

it('filters by amenities of the space or of the property', function () {
    $wifi = Amenity::factory()->scope(AmenityScope::Both)->create();
    $withWifi = listedProperty();
    $withWifi->amenities()->attach($wifi);
    listedProperty();

    expect(searchIds(['amenities' => [$wifi->id]]))->toBe([$withWifi->id]);
});

it('hides spaces blocked on the move-in date', function () {
    $blocked = listedProperty();
    $free = listedProperty();
    AvailabilityBlock::create([
        'unit_id' => $blocked->units()->firstOrFail()->id,
        'starts_on' => now()->addDays(10)->toDateString(),
        'ends_on' => now()->addDays(20)->toDateString(),
        'reason' => 'maintenance',
        'created_by' => $blocked->owner_id,
    ]);

    expect(searchIds(['move_in' => now()->addDays(5)->toDateString()]))->toBe([$free->id]);
});

it('hides spaces whose communities exclude a verified client (RN-31)', function () {
    $studentsOnly = listedProperty(['allowed_communities' => ['student']]);
    $open = listedProperty();
    $teacher = userWithRole(Role::Client);
    $teacher->verificationProfiles()->where('kind', VerificationKind::Client)->update([
        'status' => VerificationStatus::Verified, 'community_type' => CommunityType::Faculty, 'verified_until' => now()->addYear(),
    ]);

    $asGuest = searchIds();

    expect($asGuest)->toHaveCount(2)
        ->and(in_array($studentsOnly->id, $asGuest, true))->toBeTrue()
        ->and(searchIds([], $teacher->fresh()))->toBe([$open->id]);
});

it('ignores invalid filters instead of failing', function () {
    listedProperty();

    expect(searchIds(['period' => 'daily', 'radius' => 999, 'campus' => 'x']))->toHaveCount(1);
});
