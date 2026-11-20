<?php

namespace App\Modules\Identity\Actions;

use App\Models\User;
use App\Modules\Identity\Enums\CohabitationGender;

/**
 * RN-32: stores the person's gender for coexistence purposes (asked when requesting a
 * space with a gender rule). It is private: nobody else ever sees it.
 */
class DeclareCohabitationGender
{
    public function handle(User $user, CohabitationGender $gender): User
    {
        if ($user->cohabitation_gender !== $gender) {
            $user->forceFill(['cohabitation_gender' => $gender])->save();
        }

        return $user;
    }
}
