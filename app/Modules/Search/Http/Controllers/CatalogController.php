<?php

namespace App\Modules\Search\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Catalog\Models\Amenity;
use App\Modules\Catalog\Models\Campus;
use App\Modules\Identity\Enums\CohabitationGender;
use App\Modules\Pricing\Enums\RatePeriod;
use App\Modules\Properties\Enums\GenderPolicy;
use App\Modules\Properties\Enums\UnitType;
use App\Modules\Properties\Models\Property;
use App\Modules\Search\Http\Requests\CatalogRequest;
use App\Modules\Search\Presenters\ListingPresenter;
use App\Modules\Search\Support\CatalogQuery;
use App\Modules\Search\Support\Quote;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * HU-07.01 / HU-07.02: the public catalog and the listing page.
 */
class CatalogController extends Controller
{
    public const PER_PAGE = 24;

    public function __construct(private ListingPresenter $presenter) {}

    public function index(CatalogRequest $request, CatalogQuery $query): Response
    {
        $filters = $request->filters();
        /** @var User|null $viewer */
        $viewer = $request->user();
        $results = $query->search($filters, $viewer);
        $page = max(1, $request->integer('page', 1));

        $paginator = new LengthAwarePaginator(
            $results->forPage($page, self::PER_PAGE)->map(fn (array $result): array => $this->presenter->card($result))->values(),
            $results->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return Inertia::render('stays/index', [
            'listings' => $paginator,
            'filters' => [
                ...$request->only(['campus', 'radius', 'type', 'min_price', 'max_price', 'move_in', 'gender', 'sort']),
                'period' => $filters->period->value,
                'amenities' => $filters->amenityIds,
            ],
            'campus' => $filters->campus ? ['name' => $filters->campus->name, 'lat' => (float) $filters->campus->latitude, 'lng' => (float) $filters->campus->longitude] : null,
            'options' => [
                'campuses' => Campus::query()->with('institution')->whereHas('institution', fn ($institution) => $institution->where('is_active', true))->orderBy('name')->get()
                    ->map(fn (Campus $campus): array => ['value' => $campus->id, 'label' => "{$campus->institution->acronym} · {$campus->name}"]),
                'radii' => CatalogRequest::RADII,
                'periods' => RatePeriod::options(),
                'unitTypes' => array_values(array_filter(UnitType::options(), fn (array $option): bool => $option['value'] !== UnitType::SharedRoom->value)),
                'genderPolicies' => GenderPolicy::options(),
                'amenities' => Amenity::query()->active()->orderBy('name')->get()->map(fn (Amenity $amenity): array => ['value' => $amenity->id, 'label' => $amenity->name]),
            ],
        ]);
    }

    public function show(Request $request, Property $property, Quote $quote): Response
    {
        $listed = CatalogQuery::isListed($property);
        $canPreview = $request->user()?->can('view', $property) ?? false;
        abort_unless($listed || $canPreview, 404);

        $unitId = $request->integer('unit');
        $unit = $unitId ? CatalogQuery::rentableUnits($property)->firstWhere('id', $unitId) : null;
        $period = RatePeriod::tryFrom($request->string('period')->toString());
        $start = $request->filled('start') ? rescue(fn () => CarbonImmutable::parse($request->string('start')->toString())->startOfDay(), null, false) : null;

        return Inertia::render('stays/show', [
            ...$this->presenter->detail($property),
            'preview' => ! $listed,
            'quote' => $unit && $period && $start ? $quote->handle($unit, $period, $start, max(1, $request->integer('periods', 1))) : null,
            'canEdit' => $request->user() !== null && Gate::allows('update', $property),
            // RN-32: asked only when the space has a gender rule and the person never declared it.
            'needsGender' => $request->user() !== null && $request->user()->cohabitation_gender === null,
            'genderOptions' => CohabitationGender::options(),
        ]);
    }
}
