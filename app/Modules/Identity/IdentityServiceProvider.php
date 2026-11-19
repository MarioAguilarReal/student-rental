<?php

namespace App\Modules\Identity;

use App\Models\User;
use App\Modules\Identity\Policies\UserPolicy;
use App\Modules\Shared\Providers\ModuleServiceProvider;

final class IdentityServiceProvider extends ModuleServiceProvider
{
    protected array $policies = [
        User::class => UserPolicy::class,
    ];
}
