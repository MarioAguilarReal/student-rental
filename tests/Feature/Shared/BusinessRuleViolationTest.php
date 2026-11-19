<?php

use App\Modules\Shared\Exceptions\BusinessRuleViolation;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware('web')->post('/_test/business-rule', function () {
        throw new BusinessRuleViolation('RN-22', 'La unidad no está disponible en esas fechas.', 'start_date');
    });
});

it('sends the user back with the violation as a validation error', function () {
    $this->from('/previous')
        ->post('/_test/business-rule')
        ->assertRedirect('/previous')
        ->assertSessionHasErrors(['start_date' => 'La unidad no está disponible en esas fechas.']);
});

it('answers JSON clients with a 422 and the rule code', function () {
    $this->postJson('/_test/business-rule')
        ->assertUnprocessable()
        ->assertJson([
            'rule' => 'RN-22',
            'errors' => ['start_date' => ['La unidad no está disponible en esas fechas.']],
        ]);
});
