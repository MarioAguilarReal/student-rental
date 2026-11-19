<?php

use App\Modules\Identity\Enums\Permission;
use App\Modules\Identity\Enums\Role;

it('uses Spanish as the application locale', function () {
    expect(app()->getLocale())->toBe('es')
        ->and(__('validation.required', ['attribute' => 'correo']))->toBe('El campo correo es obligatorio.');
});

it('has a Spanish label for every role and permission', function () {
    foreach ([...Role::cases(), ...Permission::cases()] as $case) {
        expect($case->label())->not->toBe($case->value);
    }
});

it('shares the UI translations with the frontend', function () {
    $this->get(route('login'))
        ->assertInertia(fn ($page) => $page
            ->where('locale', 'es')
            ->where('translations.Log in', 'Iniciar sesión'));
});
