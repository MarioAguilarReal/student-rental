<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Identity\Actions\ReactivateUser;
use App\Modules\Identity\Actions\SuspendUser;
use App\Modules\Identity\Http\Requests\SuspendUserRequest;
use App\Modules\Shared\Http\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserSuspensionController extends Controller
{
    public function store(SuspendUserRequest $request, User $user, SuspendUser $suspendUser): RedirectResponse
    {
        Gate::authorize('suspend', $user);

        $suspendUser->handle($request->user(), $user, $request->string('reason')->toString());

        Toast::success(__('Account suspended.'));

        return back();
    }

    public function destroy(Request $request, User $user, ReactivateUser $reactivateUser): RedirectResponse
    {
        Gate::authorize('suspend', $user);

        $reactivateUser->handle($request->user(), $user);

        Toast::success(__('Account reactivated.'));

        return back();
    }
}
