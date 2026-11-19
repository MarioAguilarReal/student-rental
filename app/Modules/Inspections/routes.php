<?php

use App\Modules\Identity\Enums\Permission;
use App\Modules\Inspections\Http\Controllers\ChecklistController;
use App\Modules\Inspections\Http\Controllers\InspectionQueueController;
use App\Modules\Inspections\Http\Controllers\InspectorController;
use App\Modules\Inspections\Models\Inspection;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // HU-06.01: administrators
    Route::prefix('admin/inspections')->name('inspections.queue.')->group(function () {
        Route::get('/', [InspectionQueueController::class, 'index'])->can('viewAny', Inspection::class)->name('index');
        Route::get('{inspection}', [InspectionQueueController::class, 'show'])->can('viewAny', Inspection::class)->name('show');
        Route::post('{inspection}/assignment', [InspectionQueueController::class, 'assign'])->name('assign');
    });

    // HU-06.02 to HU-06.04: inspectors
    Route::middleware('can:'.Permission::InspectionsPerform->value)->group(function () {
        Route::get('inspections', [InspectorController::class, 'index'])->name('inspections.agenda');
        Route::get('inspections/{inspection}', [InspectorController::class, 'show'])->can('perform', 'inspection')->name('inspections.perform');
        Route::post('inspections/{inspection}/start', [InspectorController::class, 'start'])->name('inspections.start');
        Route::post('inspections/{inspection}/answers', [InspectorController::class, 'answer'])->name('inspections.answers.store');
        Route::post('inspections/{inspection}/complete', [InspectorController::class, 'complete'])->name('inspections.complete');
    });

    // HU-03.03: Super Admin
    Route::middleware('can:'.Permission::CatalogManage->value)->prefix('admin/checklists')->name('checklists.')->group(function () {
        Route::get('/', [ChecklistController::class, 'index'])->name('index');
        Route::post('draft', [ChecklistController::class, 'draft'])->name('draft');
        Route::post('{template}/items', [ChecklistController::class, 'storeItem'])->name('items.store');
        Route::put('{template}/items/{item}', [ChecklistController::class, 'updateItem'])->scopeBindings()->name('items.update');
        Route::delete('{template}/items/{item}', [ChecklistController::class, 'destroyItem'])->scopeBindings()->name('items.destroy');
        Route::post('{template}/publish', [ChecklistController::class, 'publish'])->name('publish');
    });
});
