<?php

namespace App\Modules\Pricing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Pricing\Actions\SaveUnitPricing;
use App\Modules\Pricing\Http\Requests\UnitPricingRequest;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Shared\Http\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class UnitPricingController extends Controller
{
    public function update(UnitPricingRequest $request, Property $property, Unit $unit, SaveUnitPricing $savePricing): RedirectResponse
    {
        Gate::authorize('update', $property);

        /** @var User $actor */
        $actor = $request->user();
        $savePricing->handle($actor, $unit, $request->plans(), $request->depositMinor());

        Toast::success(__('Prices saved for :unit.', ['unit' => $unit->name]));

        return back();
    }
}
