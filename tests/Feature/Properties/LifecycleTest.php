<?php

use App\Modules\Identity\Enums\Role;
use App\Modules\Pricing\Contracts\RatePlanCatalog;
use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * HU-04.05 to HU-04.08 · Editar, solicitar inspección, publicar/pausar y archivar.
 */
beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
});

function readyForInspection(): Property
{
    $owner = verifiedOwner();
    $property = Property::factory()->withAddress()->create(['owner_id' => $owner->id]);
    Unit::factory()->create(['property_id' => $property->id]);
    $property->addMedia(UploadedFile::fake()->create('escritura.pdf', 100, 'application/pdf'))->toMediaCollection(Property::OWNERSHIP_PROOF);

    return $property;
}

function readyToPublish(): Property
{
    $property = readyForInspection();
    $property->update(['status' => PropertyStatus::Approved, 'inspection_valid_until' => now()->addYear()]);

    foreach (range(1, 3) as $i) {
        $property->addMedia(UploadedFile::fake()->image("f{$i}.jpg"))->toMediaCollection(Property::PHOTOS);
    }

    app()->instance(RatePlanCatalog::class, new class implements RatePlanCatalog
    {
        public function hasActivePlan(Property $property): bool
        {
            return true;
        }
    });

    return $property;
}

it('requests the inspection when every RN-06 requirement is met', function () {
    $property = readyForInspection();

    $this->actingAs($property->owner)->post(route('properties.inspection.request', $property))->assertSessionHasNoErrors();

    expect($property->fresh()?->status)->toBe(PropertyStatus::PendingInspection)
        ->and($property->fresh()?->submitted_for_inspection_at)->not->toBeNull();
    $this->assertDatabaseHas('activity_log', ['event' => 'submitted_for_inspection', 'subject_id' => $property->id]);
});

it('lists what is missing before requesting the inspection (RN-06)', function () {
    $owner = userWithRole(Role::Owner); // identity not verified
    $property = Property::factory()->create(['owner_id' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('properties.inspection.request', $property))
        ->assertSessionHasErrors(['domain' => 'El propietario debe verificar su identidad. Adjunta el comprobante de propiedad. Completa la dirección y coloca el pin en el mapa. Agrega al menos un espacio.']);

    expect($property->fresh()?->status)->toBe(PropertyStatus::Draft);
});

it('stores the proof of ownership privately and replaces the previous one', function () {
    $property = readyForInspection();

    $this->actingAs($property->owner)
        ->post(route('properties.ownership-proof.store', $property), ['document' => UploadedFile::fake()->create('predial.pdf', 50, 'application/pdf')])
        ->assertSessionHasNoErrors();

    $proofs = $property->fresh()?->getMedia(Property::OWNERSHIP_PROOF);
    expect($proofs)->toHaveCount(1)->and($proofs?->first()?->disk)->toBe('local');
});

it('sends an inspected property back to inspection when its location changes (RN-11)', function () {
    $property = readyToPublish();
    $property->update(['status' => PropertyStatus::Published]);

    $this->actingAs($property->owner)->put(route('properties.location.update', $property), [
        'street' => 'Otra calle', 'exterior_number' => '10', 'neighborhood' => 'Centro', 'city' => 'Tijuana',
        'state' => 'Baja California', 'postal_code' => '22000', 'latitude' => 32.5, 'longitude' => -117.0,
    ])->assertSessionHasNoErrors();

    expect($property->fresh()?->status)->toBe(PropertyStatus::PendingInspection);
});

it('requires the map pin and a 5-digit postal code', function () {
    $property = Property::factory()->create(['owner_id' => userWithRole(Role::Owner)->id]);

    $this->actingAs($property->owner)->put(route('properties.location.update', $property), [
        'street' => 'Calle', 'exterior_number' => '1', 'neighborhood' => 'Centro', 'city' => 'X', 'state' => 'Y', 'postal_code' => '123',
    ])->assertSessionHasErrors(['postal_code', 'latitude', 'longitude']);
});

it('publishes when every RN-10 requirement is met, and pauses', function () {
    $property = readyToPublish();

    $this->actingAs($property->owner)->post(route('properties.publish', $property))->assertSessionHasNoErrors();
    expect($property->fresh()?->status)->toBe(PropertyStatus::Published);

    $this->actingAs($property->owner)->post(route('properties.pause', $property))->assertSessionHasNoErrors();
    expect($property->fresh()?->status)->toBe(PropertyStatus::Paused);

    $this->actingAs($property->owner)->post(route('properties.publish', $property))->assertSessionHasNoErrors();
    expect($property->fresh()?->status)->toBe(PropertyStatus::Published);
});

it('refuses to publish while something is missing (RN-10)', function (string $missing) {
    $property = readyToPublish();

    match ($missing) {
        'expired inspection' => $property->update(['inspection_valid_until' => now()->subDay()]),
        'photos' => $property->getFirstMedia(Property::PHOTOS)?->delete(),
        'rentable unit' => $property->units()->update(['is_rentable' => false]),
        'price plan' => app()->forgetInstance(RatePlanCatalog::class),
    };

    $this->actingAs($property->owner)->post(route('properties.publish', $property))->assertSessionHasErrors('domain');

    expect($property->fresh()?->status)->toBe(PropertyStatus::Approved);
})->with(['expired inspection', 'photos', 'rentable unit', 'price plan']);

it('cannot publish a property that has not been inspected', function () {
    $property = readyForInspection();

    $this->actingAs($property->owner)->post(route('properties.publish', $property))->assertSessionHasErrors('status');
});

it('archives and then freezes the property (RN-13)', function () {
    $property = readyForInspection();

    $this->actingAs($property->owner)->post(route('properties.archive', $property))->assertRedirect(route('properties.index'));
    expect($property->fresh()?->status)->toBe(PropertyStatus::Archived);

    $this->actingAs($property->owner)
        ->put(route('properties.update', $property), ['title' => 'Nuevo', 'property_type' => 'house', 'management_mode' => 'self_managed'])
        ->assertSessionHasErrors('domain');
});

it('must pause a published property before archiving it', function () {
    $property = readyToPublish();
    $property->update(['status' => PropertyStatus::Published]);

    $this->actingAs($property->owner)->post(route('properties.archive', $property))->assertSessionHasErrors('status');
});

it('shows the editor with the publication checklist', function () {
    $property = readyForInspection();

    $this->actingAs($property->owner)->get(route('properties.edit', $property))
        ->assertInertia(fn ($page) => $page
            ->component('properties/edit')
            ->has('units', 1)
            ->has('publication', 5)
            ->where('publication.0.met', true)
            ->where('can.requestInspection', true)
            ->whereNot('ownershipProof', null));
});
