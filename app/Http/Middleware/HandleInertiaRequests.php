<?php

namespace App\Http\Middleware;

use App\Modules\Shared\Enums\SystemParameter;
use App\Modules\Shared\Support\Parameters;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                // Lets the UI show only what the user may do; the backend still authorizes every request.
                'roles' => fn (): array => $request->user()?->getRoleNames()->values()->all() ?? [],
                'permissions' => fn (): array => $request->user()?->getAllPermissions()->pluck('name')->values()->all() ?? [],
                'unreadNotifications' => fn (): int => $request->user()?->unreadNotifications()->count() ?? 0,
            ],
            // HU-03.05: deadlines quoted in the interface follow the system parameters.
            'deadlines' => fn (): array => [
                'request_hours' => Parameters::int(SystemParameter::RentalRequestHours),
                'payment_hours' => Parameters::int(SystemParameter::RentalPaymentHours),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'locale' => app()->getLocale(),
            // UI strings for resources/js/lib/i18n.ts, sent once per locale instead of on every visit.
            'translations' => Inertia::once(fn (): array => $this->translations(app()->getLocale()))
                ->as('translations.'.app()->getLocale()),
        ];
    }

    /**
     * Frontend strings live in lang/{locale}.json, keyed by their English source text.
     *
     * @return array<array-key, mixed>
     */
    private function translations(string $locale): array
    {
        $path = lang_path("{$locale}.json");

        if (! is_file($path)) {
            return [];
        }

        $translations = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        return is_array($translations) ? $translations : [];
    }
}
