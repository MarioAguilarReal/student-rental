<?php

use App\Modules\Catalog\Enums\AmenityCategory;
use App\Modules\Catalog\Enums\AmenityScope;
use App\Modules\Identity\Enums\Permission;
use App\Modules\Identity\Enums\Role;
use App\Modules\Properties\Enums\GenderPolicy;
use App\Modules\Properties\Enums\ManagementMode;
use App\Modules\Properties\Enums\PauseReason;
use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Enums\PropertyType;
use App\Modules\Properties\Enums\UnitStatus;
use App\Modules\Properties\Enums\UnitTemplate;
use App\Modules\Properties\Enums\UnitType;
use App\Modules\Verification\Enums\CommunityType;
use App\Modules\Verification\Enums\DocumentStatus;
use App\Modules\Verification\Enums\DocumentType;
use App\Modules\Verification\Enums\VerificationKind;
use App\Modules\Verification\Enums\VerificationStatus;

it('uses Spanish as the application locale', function () {
    expect(app()->getLocale())->toBe('es')
        ->and(__('validation.required', ['attribute' => 'correo']))->toBe('El campo correo es obligatorio.');
});

it('has a Spanish label for every enum case shown to users', function () {
    $enums = [
        Role::class, Permission::class, VerificationKind::class, VerificationStatus::class, CommunityType::class, DocumentType::class, DocumentStatus::class,
        AmenityScope::class, AmenityCategory::class,
        ManagementMode::class, PropertyType::class, PropertyStatus::class,
        PauseReason::class, UnitType::class, UnitStatus::class,
        GenderPolicy::class, UnitTemplate::class,
    ];

    foreach (collect($enums)->flatMap(fn (string $enum) => $enum::cases()) as $case) {
        expect($case->label())->not->toBe($case->value);
    }
});

it('shares the UI translations with the frontend', function () {
    $this->get(route('login'))
        ->assertInertia(fn ($page) => $page
            ->where('locale', 'es')
            ->where('translations.Log in', 'Iniciar sesión'));
});
