<?php

use App\Modules\Audit\Http\Controllers\AuditLogController;
use App\Modules\Identity\Enums\Permission;
use Illuminate\Support\Facades\Route;

// HU-11.01: read only for administrators (RN-90).
Route::middleware(['auth', 'verified', 'can:'.Permission::AuditView->value])
    ->get('admin/audit', [AuditLogController::class, 'index'])
    ->name('audit.index');
