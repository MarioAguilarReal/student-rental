<?php

use App\Modules\Identity\Enums\Role;
use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * HU-04.03 · Fotos.
 */
beforeEach(function () {
    Storage::fake('public');
    $this->owner = userWithRole(Role::Owner);
    $this->property = Property::factory()->create(['owner_id' => $this->owner->id, 'status' => PropertyStatus::Published]);
});

function photos(int $count): array
{
    return array_map(fn (int $i) => UploadedFile::fake()->image("foto{$i}.jpg", 800, 600), range(1, $count));
}

it('uploads several photos, optionally tied to a space, without a new inspection', function () {
    $unit = Unit::factory()->create(['property_id' => $this->property->id]);

    $this->actingAs($this->owner)
        ->post(route('properties.photos.store', $this->property), ['photos' => photos(2), 'unit_id' => $unit->id])
        ->assertSessionHasNoErrors();

    $media = $this->property->getMedia(Property::PHOTOS);
    expect($media)->toHaveCount(2)
        ->and($media->first()?->getCustomProperty('unit_id'))->toBe($unit->id)
        ->and($media->first()?->hasGeneratedConversion('thumb'))->toBeTrue()
        ->and($this->property->fresh()?->status)->toBe(PropertyStatus::Published);
});

it('accepts only images up to 10 MB', function (UploadedFile $file) {
    $this->actingAs($this->owner)
        ->post(route('properties.photos.store', $this->property), ['photos' => [$file]])
        ->assertSessionHasErrors('photos.0');
})->with([
    'pdf' => fn () => UploadedFile::fake()->create('plano.pdf', 100, 'application/pdf'),
    'too big' => fn () => UploadedFile::fake()->image('enorme.jpg')->size(10_241),
]);

it('caps a property at 30 photos', function () {
    foreach (photos(29) as $file) {
        $this->property->addMedia($file)->toMediaCollection(Property::PHOTOS);
    }

    $this->actingAs($this->owner)
        ->post(route('properties.photos.store', $this->property), ['photos' => photos(2)])
        ->assertSessionHasErrors('photos');
});

it('reorders photos, sets the cover and the alternative text', function () {
    foreach (photos(3) as $file) {
        $this->property->addMedia($file)->toMediaCollection(Property::PHOTOS);
    }
    [$first, $second, $third] = $this->property->getMedia(Property::PHOTOS)->pluck('id')->all();

    $this->actingAs($this->owner)->put(route('properties.photos.reorder', $this->property), ['order' => [$third, $first, $second]]);
    expect($this->property->fresh()?->getMedia(Property::PHOTOS)->pluck('id')->all())->toBe([$third, $first, $second]);

    $this->actingAs($this->owner)->put(route('properties.photos.update', [$this->property, $second]), ['alt' => 'Sala con luz natural', 'cover' => true]);
    $photos = $this->property->fresh()?->getMedia(Property::PHOTOS);
    expect($photos?->first()?->id)->toBe($second)
        ->and($photos?->first()?->getCustomProperty('alt'))->toBe('Sala con luz natural');
});

it('does not touch photos of other properties', function () {
    $other = Property::factory()->create(['owner_id' => $this->owner->id]);
    $foreign = $other->addMedia(UploadedFile::fake()->image('x.jpg'))->toMediaCollection(Property::PHOTOS);

    $this->actingAs($this->owner)
        ->delete(route('properties.photos.destroy', [$this->property, $foreign->id]))
        ->assertSessionHasErrors('photos');

    expect($other->fresh()?->getMedia(Property::PHOTOS))->toHaveCount(1);
});
