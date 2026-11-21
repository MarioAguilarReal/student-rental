<?php

namespace App\Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Payments\Actions\RemoveScheduledCommissionRule;
use App\Modules\Payments\Actions\ScheduleCommissionRule;
use App\Modules\Payments\Http\Requests\CommissionRuleRequest;
use App\Modules\Payments\Models\CommissionRule;
use App\Modules\Properties\Enums\ManagementMode;
use App\Modules\Shared\Http\Toast;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * HU-03.04: commission rates by management mode, with the dates they apply.
 */
class CommissionRuleController extends Controller
{
    public function index(): Response
    {
        $today = CarbonImmutable::today();
        $rules = CommissionRule::query()->orderByDesc('valid_from')->get()->groupBy(fn (CommissionRule $rule) => $rule->management_mode->value);

        return Inertia::render('payments/commission-rules', [
            'today' => $today->toDateString(),
            'modes' => collect(ManagementMode::cases())->map(fn (ManagementMode $mode): array => [
                'value' => $mode->value,
                'label' => $mode->label(),
                'rules' => ($rules[$mode->value] ?? collect())->map(fn (CommissionRule $rule): array => [
                    'id' => $rule->id,
                    'percentage_bp' => $rule->percentage_bp,
                    'valid_from' => $rule->valid_from->toDateString(),
                    'valid_until' => $rule->valid_until?->toDateString(),
                    'state' => match (true) {
                        $rule->valid_from->gt($today) => 'scheduled',
                        $rule->valid_until !== null && $rule->valid_until->lt($today) => 'past',
                        default => 'current',
                    },
                ])->values(),
            ])->values(),
        ]);
    }

    public function store(CommissionRuleRequest $request, ScheduleCommissionRule $schedule): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $schedule->handle(
            $actor,
            $request->enum('management_mode', ManagementMode::class) ?? ManagementMode::SelfManaged,
            $request->basisPoints(),
            CarbonImmutable::parse($request->string('valid_from')->toString()),
        );

        Toast::success(__('Commission rate scheduled.'));

        return back();
    }

    public function destroy(Request $request, CommissionRule $commissionRule, RemoveScheduledCommissionRule $remove): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $remove->handle($actor, $commissionRule);

        Toast::success(__('Scheduled rate removed.'));

        return back();
    }
}
