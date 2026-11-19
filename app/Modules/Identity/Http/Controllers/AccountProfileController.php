<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Actions\AddAccountProfile;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Http\Requests\AddAccountProfileRequest;
use App\Modules\Shared\Http\Toast;
use Illuminate\Http\RedirectResponse;

class AccountProfileController extends Controller
{
    public function store(AddAccountProfileRequest $request, AddAccountProfile $addAccountProfile): RedirectResponse
    {
        $role = Role::from($request->string('role')->toString());

        $addAccountProfile->handle($request->user(), $role);

        Toast::success(__('Profile added. Complete its verification.'));

        return to_route('verifications.show');
    }
}
