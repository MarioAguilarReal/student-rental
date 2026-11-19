<?php

namespace App\Modules\Properties\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Properties\Actions\ApplyUnitTemplate;
use App\Modules\Properties\Actions\RemoveUnit;
use App\Modules\Properties\Actions\SaveUnit;
use App\Modules\Properties\Enums\GenderPolicy;
use App\Modules\Properties\Enums\UnitTemplate;
use App\Modules\Properties\Enums\UnitType;
use App\Modules\Properties\Http\Requests\ApplyUnitTemplateRequest;
use App\Modules\Properties\Http\Requests\UnitRequest;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Shared\Http\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class UnitController extends Controller
{
    public function store(UnitRequest $request, Property $property, SaveUnit $saveUnit): RedirectResponse
    {
        return $this->save($request, $property, null, $saveUnit, __('Space added.'));
    }

    public function update(UnitRequest $request, Property $property, Unit $unit, SaveUnit $saveUnit): RedirectResponse
    {
        return $this->save($request, $property, $unit, $saveUnit, __('Space updated.'));
    }

    public function destroy(Property $property, Unit $unit, RemoveUnit $removeUnit): RedirectResponse
    {
        Gate::authorize('update', $property);

        $removeUnit->handle($unit);
        Toast::success(__('Space removed.'));

        return back();
    }

    public function template(ApplyUnitTemplateRequest $request, Property $property, ApplyUnitTemplate $applyTemplate): RedirectResponse
    {
        Gate::authorize('update', $property);

        $applyTemplate->handle(
            $property,
            UnitTemplate::from($request->string('template')->toString()),
            $request->integer('rooms'),
            $request->integer('capacity'),
        );
        Toast::success(__('Spaces created. Adjust them as needed.'));

        return back();
    }

    private function save(UnitRequest $request, Property $property, ?Unit $unit, SaveUnit $saveUnit, string $message): RedirectResponse
    {
        Gate::authorize('update', $property);

        $saveUnit->handle(
            $property,
            $unit,
            $request->filled('parent_id') ? $property->units()->findOrFail($request->integer('parent_id')) : null,
            UnitType::from($request->string('type')->toString()),
            $request->string('name')->toString(),
            $request->input('description'),
            $request->integer('capacity'),
            $request->boolean('is_rentable'),
            GenderPolicy::tryFrom($request->string('gender_policy')->toString()),
            $request->allowedCommunities(),
            array_values(array_map('intval', $request->array('amenity_ids'))),
        );
        Toast::success($message);

        return back();
    }
}
