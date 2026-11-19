<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Actions\SaveInstitution;
use App\Modules\Catalog\Http\Requests\InstitutionRequest;
use App\Modules\Catalog\Models\Campus;
use App\Modules\Catalog\Models\Institution;
use App\Modules\Shared\Http\Toast;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InstitutionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('catalog/institutions/index', [
            'institutions' => Institution::query()
                ->withCount('campuses')
                ->orderBy('name')
                ->get()
                ->map(fn (Institution $institution): array => [
                    'id' => $institution->id,
                    'name' => $institution->name,
                    'acronym' => $institution->acronym,
                    'email_domains' => $institution->email_domains,
                    'is_active' => $institution->is_active,
                    'campuses_count' => $institution->campuses_count,
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('catalog/institutions/form', ['institution' => null]);
    }

    public function store(InstitutionRequest $request, SaveInstitution $saveInstitution): RedirectResponse
    {
        $institution = $saveInstitution->handle(
            null,
            $request->string('name')->toString(),
            $request->input('acronym'),
            $request->input('email_domains'),
            $request->boolean('is_active', true),
        );

        Toast::success(__('Institution created. Now add its campuses.'));

        return to_route('catalog.institutions.edit', $institution);
    }

    public function edit(Institution $institution): Response
    {
        return Inertia::render('catalog/institutions/form', [
            'institution' => [
                'id' => $institution->id,
                'name' => $institution->name,
                'acronym' => $institution->acronym,
                'email_domains' => $institution->email_domains,
                'is_active' => $institution->is_active,
                'campuses' => $institution->campuses->map(fn (Campus $campus): array => [
                    'id' => $campus->id,
                    'name' => $campus->name,
                    'address' => $campus->address,
                    'latitude' => $campus->latitude,
                    'longitude' => $campus->longitude,
                ]),
            ],
        ]);
    }

    public function update(InstitutionRequest $request, Institution $institution, SaveInstitution $saveInstitution): RedirectResponse
    {
        $saveInstitution->handle(
            $institution,
            $request->string('name')->toString(),
            $request->input('acronym'),
            $request->input('email_domains'),
            $request->boolean('is_active'),
        );

        Toast::success(__('Institution updated.'));

        return back();
    }
}
