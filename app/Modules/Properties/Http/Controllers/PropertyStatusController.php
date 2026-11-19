<?php

namespace App\Modules\Properties\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Properties\Actions\ArchiveProperty;
use App\Modules\Properties\Actions\PauseProperty;
use App\Modules\Properties\Actions\PublishProperty;
use App\Modules\Properties\Actions\SubmitPropertyForInspection;
use App\Modules\Properties\Models\Property;
use App\Modules\Shared\Http\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * HU-04.06 to HU-04.08: moving a property through its life cycle.
 */
class PropertyStatusController extends Controller
{
    public function inspection(Property $property, SubmitPropertyForInspection $submit): RedirectResponse
    {
        Gate::authorize('requestInspection', $property);

        $submit->handle($property);
        Toast::success(__('Inspection requested. We will let you know the date.'));

        return back();
    }

    public function publish(Request $request, Property $property, PublishProperty $publish): RedirectResponse
    {
        Gate::authorize('publish', $property);

        $publish->handle($this->actor($request), $property);
        Toast::success(__('Property published.'));

        return back();
    }

    public function pause(Request $request, Property $property, PauseProperty $pause): RedirectResponse
    {
        Gate::authorize('publish', $property);

        $pause->handle($this->actor($request), $property);
        Toast::success(__('Property paused. It no longer appears in the catalog.'));

        return back();
    }

    public function archive(Request $request, Property $property, ArchiveProperty $archive): RedirectResponse
    {
        Gate::authorize('archive', $property);

        $archive->handle($this->actor($request), $property);
        Toast::success(__('Property archived.'));

        return to_route('properties.index');
    }

    private function actor(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
