<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Actions\SaveCampus;
use App\Modules\Catalog\Http\Requests\CampusRequest;
use App\Modules\Catalog\Models\Campus;
use App\Modules\Catalog\Models\Institution;
use App\Modules\Shared\Http\Toast;
use Illuminate\Http\RedirectResponse;

class CampusController extends Controller
{
    public function store(CampusRequest $request, Institution $institution, SaveCampus $saveCampus): RedirectResponse
    {
        $saveCampus->handle(
            $institution,
            null,
            $request->string('name')->toString(),
            $request->input('address'),
            $request->float('latitude'),
            $request->float('longitude'),
        );

        Toast::success(__('Campus added.'));

        return back();
    }

    public function update(CampusRequest $request, Institution $institution, Campus $campus, SaveCampus $saveCampus): RedirectResponse
    {
        $saveCampus->handle(
            $institution,
            $campus,
            $request->string('name')->toString(),
            $request->input('address'),
            $request->float('latitude'),
            $request->float('longitude'),
        );

        Toast::success(__('Campus updated.'));

        return back();
    }

    public function destroy(Institution $institution, Campus $campus): RedirectResponse
    {
        $campus->delete();

        Toast::success(__('Campus deleted.'));

        return back();
    }
}
