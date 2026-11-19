<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Http\Requests\HouseRuleRequest;
use App\Modules\Catalog\Models\HouseRule;
use App\Modules\Shared\Http\Toast;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * HU-03.02. Simple catalog edits with no business rules, so no Action is needed.
 */
class HouseRuleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('catalog/house-rules/index', [
            'houseRules' => HouseRule::query()->orderBy('name')->get(['id', 'name', 'description', 'is_active']),
        ]);
    }

    public function store(HouseRuleRequest $request): RedirectResponse
    {
        HouseRule::create([...$request->validated(), 'is_active' => $request->boolean('is_active', true)]);
        Toast::success(__('House rule added.'));

        return back();
    }

    public function update(HouseRuleRequest $request, HouseRule $houseRule): RedirectResponse
    {
        $houseRule->update([...$request->validated(), 'is_active' => $request->boolean('is_active')]);
        Toast::success(__('House rule updated.'));

        return back();
    }
}
