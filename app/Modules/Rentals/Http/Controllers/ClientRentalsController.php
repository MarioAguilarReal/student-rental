<?php

namespace App\Modules\Rentals\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Identity\Enums\CohabitationGender;
use App\Modules\Pricing\Enums\RatePeriod;
use App\Modules\Properties\Models\Property;
use App\Modules\Rentals\Actions\RequestRental;
use App\Modules\Rentals\Http\Requests\RequestRentalRequest;
use App\Modules\Rentals\Models\Rental;
use App\Modules\Rentals\Presenters\RentalPresenter;
use App\Modules\Shared\Http\Toast;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * HU-08.01 (request a rental) and HU-08.04 (my rentals).
 */
class ClientRentalsController extends Controller
{
    public function __construct(private RentalPresenter $presenter) {}

    public function index(Request $request): Response
    {
        /** @var User $client */
        $client = $request->user();

        $rentals = Rental::query()
            ->with(['property', 'unit', 'client'])
            ->where('client_id', $client->id)
            ->latest('id')
            ->paginate(20)
            ->through(fn (Rental $rental): array => $this->presenter->summary($rental));

        return Inertia::render('rentals/index', ['rentals' => $rentals]);
    }

    public function store(RequestRentalRequest $request, Property $property, RequestRental $requestRental): RedirectResponse
    {
        /** @var User $client */
        $client = $request->user();
        $gender = $request->filled('gender') ? CohabitationGender::from($request->string('gender')->toString()) : null;

        $rental = $requestRental->handle(
            $client,
            $property->units()->findOrFail($request->integer('unit_id')),
            RatePeriod::from($request->string('period')->toString()),
            CarbonImmutable::createFromFormat('Y-m-d', $request->string('starts_on')->toString())?->startOfDay() ?? CarbonImmutable::today(),
            $request->integer('periods'),
            $request->boolean('accept_rules'),
            $request->input('message'),
            $gender,
        );

        Toast::success(__('Request sent. The host has 72 hours to answer.'));

        return to_route('rentals.show', $rental);
    }
}
