<?php

use App\Modules\Audit\AuditServiceProvider;
use App\Modules\Catalog\CatalogServiceProvider;
use App\Modules\Identity\IdentityServiceProvider;
use App\Modules\Inspections\InspectionsServiceProvider;
use App\Modules\Notifications\NotificationsServiceProvider;
use App\Modules\Payments\PaymentsServiceProvider;
use App\Modules\Pricing\PricingServiceProvider;
use App\Modules\Properties\PropertiesServiceProvider;
use App\Modules\Rentals\RentalsServiceProvider;
use App\Modules\Verification\VerificationServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,

    // Business modules (docs/spec/07-arquitectura.md)
    IdentityServiceProvider::class,
    VerificationServiceProvider::class,
    CatalogServiceProvider::class,
    PropertiesServiceProvider::class,
    PricingServiceProvider::class,
    RentalsServiceProvider::class,
    PaymentsServiceProvider::class,
    InspectionsServiceProvider::class,
    NotificationsServiceProvider::class,
    AuditServiceProvider::class,
];
