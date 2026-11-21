<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Catalog\Actions\UpdateSystemParameters;
use App\Modules\Catalog\Http\Requests\SystemParametersRequest;
use App\Modules\Shared\Enums\SystemParameter;
use App\Modules\Shared\Http\Toast;
use App\Modules\Shared\Support\Parameters;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * HU-03.05: deadlines, validities and price factors.
 */
class SystemParameterController extends Controller
{
    public function edit(): Response
    {
        $values = Parameters::all();

        return Inertia::render('catalog/parameters', [
            'parameters' => array_map(fn (SystemParameter $parameter): array => [
                'key' => $parameter->value,
                'label' => $parameter->label(),
                'area' => $parameter->area(),
                'unit' => $parameter->unit(),
                'value' => $values[$parameter->value],
                'default' => $parameter->default(),
                'min' => $parameter->range()[0],
                'max' => $parameter->range()[1],
                'decimal' => $parameter->isDecimal(),
            ], SystemParameter::cases()),
        ]);
    }

    public function update(SystemParametersRequest $request, UpdateSystemParameters $update): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $changes = $update->handle($actor, $request->parameterValues());

        Toast::success($changes === [] ? __('Nothing changed.') : __('Parameters saved. They apply from now on.'));

        return back();
    }
}
