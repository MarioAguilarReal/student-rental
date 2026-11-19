<?php

namespace App\Modules\Properties\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Inspections\Presenters\InspectionPresenter;
use App\Modules\Pricing\Presenters\PricingPresenter;
use App\Modules\Properties\Actions\CreateProperty;
use App\Modules\Properties\Actions\UpdatePropertyDetails;
use App\Modules\Properties\Enums\ManagementMode;
use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Enums\PropertyType;
use App\Modules\Properties\Http\Requests\StorePropertyRequest;
use App\Modules\Properties\Http\Requests\UpdatePropertyDetailsRequest;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Presenters\PropertyPresenter;
use App\Modules\Shared\Http\Toast;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PropertyController extends Controller
{
    public function __construct(private PropertyPresenter $presenter) {}

    /**
     * HU-04.01: the owner's own properties.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return $this->list(Property::query()->where('owner_id', $user->id), 'mine', $request);
    }

    /**
     * HU-04.09: every property, for administrators.
     */
    public function adminIndex(Request $request): Response
    {
        $search = trim($request->string('search')->toString());

        $query = Property::query()
            ->when(ManagementMode::tryFrom($request->string('mode')->toString()), fn ($query, $mode) => $query->where('management_mode', $mode))
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->whereLike('title', "%{$search}%")
                ->orWhereHas('owner', fn ($owner) => $owner->whereLike('email', "%{$search}%"))));

        return $this->list($query, 'all', $request);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('properties/create', [
            ...$this->presenter->formOptions(),
            'canCreateOnBehalf' => $request->user()?->can('createOnBehalf', Property::class) ?? false,
        ]);
    }

    public function store(StorePropertyRequest $request, CreateProperty $createProperty): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $owner = $actor;

        if ($request->filled('owner_email') && $actor->can('createOnBehalf', Property::class)) {
            $owner = User::query()->where('email', $request->string('owner_email')->toString())->firstOrFail();
        }

        $property = $createProperty->handle(
            $actor,
            $owner,
            $request->string('title')->toString(),
            PropertyType::from($request->string('property_type')->toString()),
            ManagementMode::from($request->string('management_mode')->toString()),
        );

        Toast::success(__('Property created. Complete its information.'));

        return to_route('properties.edit', $property);
    }

    public function edit(Request $request, Property $property, PricingPresenter $pricingPresenter, InspectionPresenter $inspectionPresenter): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('properties/edit', [
            ...$this->presenter->forEditing($property, $user),
            'pricing' => $pricingPresenter->forProperty($property),
            'inspection' => $inspectionPresenter->latestForProperty($property),
        ]);
    }

    public function update(UpdatePropertyDetailsRequest $request, Property $property, UpdatePropertyDetails $updateDetails): RedirectResponse
    {
        Gate::authorize('update', $property);

        $updateDetails->handle(
            $property,
            $request->string('title')->toString(),
            $request->input('description'),
            PropertyType::from($request->string('property_type')->toString()),
            ManagementMode::from($request->string('management_mode')->toString()),
        );

        Toast::success(__('Changes saved.'));

        return back();
    }

    /**
     * Status chips count the whole scope (before the status filter), zero included.
     *
     * @param  Builder<Property>  $query
     */
    private function list(Builder $query, string $scope, Request $request): Response
    {
        $counts = (clone $query)->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $status = PropertyStatus::tryFrom($request->string('status')->toString());

        $properties = $query
            ->when($status, fn ($query) => $query->where('status', $status))
            ->with('owner')->withCount('units')->latest('updated_at')->paginate(12)->withQueryString();

        return Inertia::render('properties/index', [
            'scope' => $scope,
            'properties' => $properties->through(fn (Property $property): array => $this->presenter->summary($property)),
            'filters' => $request->only(['status', 'mode', 'search']),
            'statusCounts' => [
                'all' => (int) $counts->sum(),
                ...collect(PropertyStatus::cases())->mapWithKeys(fn (PropertyStatus $case) => [$case->value => (int) ($counts[$case->value] ?? 0)])->all(),
            ],
            'statusOptions' => PropertyStatus::options(),
            'modeOptions' => ManagementMode::options(),
            'canCreate' => $request->user()?->can('create', Property::class) ?? false,
        ]);
    }
}
