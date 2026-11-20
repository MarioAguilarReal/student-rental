<?php

namespace App\Modules\Rentals\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Rentals\Enums\RentalStatus;
use App\Modules\Rentals\Models\Rental;
use App\Modules\Rentals\Presenters\RentalPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * HU-08.02 / HU-08.05: requests to answer and rentals of the properties the user owns
 * (administrators see every property).
 */
class ManageRentalsController extends Controller
{
    public function __construct(private RentalPresenter $presenter) {}

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $status = RentalStatus::tryFrom($request->string('status')->toString()) ?? RentalStatus::Requested;

        /** @var Builder<Rental> $scope */
        $scope = Rental::query()->when(
            ! Gate::allows('viewAll', Rental::class),
            fn (Builder $query) => $query->whereHas('property', fn (Builder $property) => $property->where('owner_id', $user->id)),
        );

        $counts = (clone $scope)->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $rentals = (clone $scope)
            ->with(['property', 'unit', 'client'])
            ->where('status', $status)
            ->when($status === RentalStatus::Requested, fn ($query) => $query->oldest('id'), fn ($query) => $query->latest('starts_on'))
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Rental $rental): array => $this->presenter->summary($rental));

        return Inertia::render('rentals/manage', [
            'rentals' => $rentals,
            'status' => $status->value,
            'statusCounts' => collect(RentalStatus::cases())->mapWithKeys(fn (RentalStatus $case) => [$case->value => (int) ($counts[$case->value] ?? 0)]),
            'statusOptions' => RentalStatus::options(),
        ]);
    }
}
