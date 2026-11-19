<?php

namespace App\Modules\Properties;

use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Policies\PropertyPolicy;
use App\Modules\Shared\Providers\ModuleServiceProvider;

final class PropertiesServiceProvider extends ModuleServiceProvider
{
    protected array $policies = [
        Property::class => PropertyPolicy::class,
    ];
}
