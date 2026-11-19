<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Identity\Actions\SyncInternalRoles;
use App\Modules\Identity\Http\Requests\UpdateUserRolesRequest;
use App\Modules\Shared\Http\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class UserRoleController extends Controller
{
    public function update(UpdateUserRolesRequest $request, User $user, SyncInternalRoles $syncInternalRoles): RedirectResponse
    {
        Gate::authorize('assignRoles', $user);

        $syncInternalRoles->handle($request->user(), $user, $request->roles());

        Toast::success(__('Roles updated.'));

        return back();
    }
}
