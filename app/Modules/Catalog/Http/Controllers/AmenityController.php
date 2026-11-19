<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Enums\AmenityCategory;
use App\Modules\Catalog\Enums\AmenityScope;
use App\Modules\Catalog\Http\Requests\AmenityRequest;
use App\Modules\Catalog\Models\Amenity;
use App\Modules\Shared\Http\Toast;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * HU-03.02. Simple catalog edits with no business rules, so no Action is needed.
 */
class AmenityController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('catalog/amenities/index', [
            'amenities' => Amenity::query()->orderBy('category')->orderBy('name')->get()
                ->map(fn (Amenity $amenity): array => [
                    'id' => $amenity->id,
                    'name' => $amenity->name,
                    'category' => $amenity->category->value,
                    'category_label' => $amenity->category->label(),
                    'scope' => $amenity->scope->value,
                    'scope_label' => $amenity->scope->label(),
                    'is_active' => $amenity->is_active,
                ]),
            'categories' => AmenityCategory::options(),
            'scopes' => AmenityScope::options(),
        ]);
    }

    public function store(AmenityRequest $request): RedirectResponse
    {
        Amenity::create([...$request->validated(), 'is_active' => $request->boolean('is_active', true)]);
        Toast::success(__('Amenity added.'));

        return back();
    }

    public function update(AmenityRequest $request, Amenity $amenity): RedirectResponse
    {
        $amenity->update([...$request->validated(), 'is_active' => $request->boolean('is_active')]);
        Toast::success(__('Amenity updated.'));

        return back();
    }
}
