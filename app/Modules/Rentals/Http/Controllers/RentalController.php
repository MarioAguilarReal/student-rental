<?php

namespace App\Modules\Rentals\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Rentals\Actions\ApproveRental;
use App\Modules\Rentals\Actions\CancelRental;
use App\Modules\Rentals\Actions\RejectRental;
use App\Modules\Rentals\Enums\RentalStatus;
use App\Modules\Rentals\Http\Requests\ReasonRequest;
use App\Modules\Rentals\Models\Rental;
use App\Modules\Rentals\Presenters\RentalPresenter;
use App\Modules\Shared\Http\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * One rental, for its client, its operator, its owner or an administrator.
 * HU-08.02 (approve / reject), HU-08.04 (client's view), HU-08.07 (cancel).
 */
class RentalController extends Controller
{
    public function __construct(private RentalPresenter $presenter) {}

    public function show(Request $request, Rental $rental): Response
    {
        /** @var User $viewer */
        $viewer = $request->user();

        return Inertia::render('rentals/show', [
            'rental' => $this->presenter->detail($rental, $viewer),
            'isClient' => $rental->client_id === $viewer->id,
            'canDecide' => Gate::allows('decide', $rental) && $rental->status === RentalStatus::Requested,
            'canCancel' => Gate::allows('cancel', $rental) && $rental->status->canTransitionTo(RentalStatus::Cancelled),
        ]);
    }

    public function approve(Request $request, Rental $rental, ApproveRental $approve): RedirectResponse
    {
        /** @var User $operator */
        $operator = $request->user();
        $approve->handle($operator, $rental);

        Toast::success(__('Request approved. The client has 48 hours to pay the first charge.'));

        return back();
    }

    public function reject(ReasonRequest $request, Rental $rental, RejectRental $reject): RedirectResponse
    {
        /** @var User $operator */
        $operator = $request->user();
        $reject->handle($operator, $rental, $request->string('reason')->toString());

        Toast::success(__('Request rejected. The client has been notified.'));

        return back();
    }

    public function cancel(ReasonRequest $request, Rental $rental, CancelRental $cancel): RedirectResponse
    {
        /** @var User $admin */
        $admin = $request->user();
        $cancel->handle($admin, $rental, $request->string('reason')->toString());

        Toast::success(__('Rental cancelled. Both parties have been notified.'));

        return back();
    }
}
