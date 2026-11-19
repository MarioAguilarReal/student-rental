<?php

namespace App\Modules\Properties\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Properties\Actions\AddPropertyPhotos;
use App\Modules\Properties\Actions\UpdatePropertyPhoto;
use App\Modules\Properties\Http\Requests\PhotoRequest;
use App\Modules\Properties\Models\Property;
use App\Modules\Shared\Http\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PhotoController extends Controller
{
    public function store(PhotoRequest $request, Property $property, AddPropertyPhotos $addPhotos): RedirectResponse
    {
        Gate::authorize('update', $property);

        /** @var list<UploadedFile> $files */
        $files = array_values(array_filter((array) $request->file('photos'), fn ($file) => $file instanceof UploadedFile));
        $unit = $request->filled('unit_id') ? $property->units()->findOrFail($request->integer('unit_id')) : null;

        $addPhotos->handle($property, $files, $unit);
        Toast::success(__('Photos uploaded.'));

        return back();
    }

    public function update(Request $request, Property $property, Media $photo, UpdatePropertyPhoto $updatePhoto): RedirectResponse
    {
        Gate::authorize('update', $property);
        $request->validate([
            'alt' => ['nullable', 'string', 'max:200'],
            'unit_id' => ['nullable', 'integer'],
            'cover' => ['boolean'],
        ]);

        $updatePhoto->handle(
            $property,
            $photo,
            $request->input('alt'),
            $request->filled('unit_id') ? $request->integer('unit_id') : null,
            $request->boolean('cover'),
        );
        Toast::success(__('Photo updated.'));

        return back();
    }

    public function reorder(Request $request, Property $property, UpdatePropertyPhoto $updatePhoto): RedirectResponse
    {
        Gate::authorize('update', $property);
        $request->validate(['order' => ['required', 'array'], 'order.*' => ['integer']]);

        $updatePhoto->reorder($property, array_values(array_map('intval', $request->array('order'))));

        return back();
    }

    public function destroy(Property $property, Media $photo, UpdatePropertyPhoto $updatePhoto): RedirectResponse
    {
        Gate::authorize('update', $property);

        $updatePhoto->delete($property, $photo);
        Toast::success(__('Photo deleted.'));

        return back();
    }
}
