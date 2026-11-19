<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Identity\Enums\Role;
use App\Modules\Verification\Models\VerificationProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim($request->string('search')->toString());
        $role = Role::tryFrom($request->string('role')->toString());

        $users = User::query()
            ->with('roles')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->whereRaw("concat_ws(' ', first_name, last_name) ilike ?", ["%{$search}%"])
                ->orWhereLike('email', "%{$search}%")))
            ->when($role, fn ($query) => $query->role($role->value))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => Role::labelsOf($user),
                'suspended' => $user->isSuspended(),
            ]);

        return Inertia::render('identity/users/index', [
            'users' => $users,
            'filters' => ['search' => $search, 'role' => $role?->value],
            'roleOptions' => Role::options(),
        ]);
    }

    public function show(Request $request, User $user): Response
    {
        $user->load(['roles', 'verificationProfiles.institution']);
        $actor = $request->user();

        return Inertia::render('identity/users/show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified' => $user->hasVerifiedEmail(),
                'birth_date' => $user->birth_date?->toDateString(),
                'created_at' => $user->created_at?->toDateString(),
                'roles' => Role::labelsOf($user),
                'role_values' => Role::valuesOf($user),
                'suspended_at' => $user->suspended_at?->toDateTimeString(),
                'suspension_reason' => $user->suspension_reason,
                'verification_profiles' => $user->verificationProfiles->map(fn (VerificationProfile $profile): array => [
                    'id' => $profile->id,
                    'kind' => $profile->kind->label(),
                    'status' => $profile->status->value,
                    'status_label' => $profile->status->label(),
                    'institution' => $profile->institution?->displayName(),
                    'verified_until' => $profile->verified_until?->toDateString(),
                ]),
            ],
            'internalRoleOptions' => array_values(array_filter(
                Role::options(),
                fn (array $option): bool => ! in_array(Role::from((string) $option['value']), Role::selfAssignable(), true),
            )),
            'can' => [
                'assignRoles' => $actor !== null && $actor->can('assignRoles', $user),
                'suspend' => $actor !== null && $actor->can('suspend', $user) && ! $actor->is($user),
            ],
        ]);
    }
}
