<?php

namespace App\Modules\Pricing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Pricing\Actions\AddAvailabilityBlock;
use App\Modules\Pricing\Actions\RemoveAvailabilityBlock;
use App\Modules\Pricing\Enums\BlockReason;
use App\Modules\Pricing\Http\Requests\AvailabilityBlockRequest;
use App\Modules\Pricing\Models\AvailabilityBlock;
use App\Modules\Properties\Models\Property;
use App\Modules\Shared\Http\Toast;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AvailabilityBlockController extends Controller
{
    public function store(AvailabilityBlockRequest $request, Property $property, AddAvailabilityBlock $addBlock): RedirectResponse
    {
        Gate::authorize('update', $property);

        /** @var User $actor */
        $actor = $request->user();
        $addBlock->handle(
            $actor,
            $property->units()->findOrFail($request->integer('unit_id')),
            CarbonImmutable::parse($request->string('starts_on')->toString()),
            CarbonImmutable::parse($request->string('ends_on')->toString()),
            BlockReason::from($request->string('reason')->toString()),
            $request->input('note'),
        );

        Toast::success(__('Dates blocked.'));

        return back();
    }

    public function destroy(Property $property, AvailabilityBlock $block, RemoveAvailabilityBlock $removeBlock): RedirectResponse
    {
        Gate::authorize('update', $property);
        abort_unless($block->unit->property_id === $property->id, 404);

        $removeBlock->handle($block);
        Toast::success(__('Block removed.'));

        return back();
    }
}
