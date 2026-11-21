<?php

namespace App\Modules\Audit\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Audit\Enums\AuditLog;
use App\Modules\Audit\Http\Requests\AuditLogRequest;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

/**
 * HU-11.01 · RN-90: who changed what and when, newest first. Read only: there is no route
 * that edits or deletes a record.
 */
class AuditLogController extends Controller
{
    public function index(AuditLogRequest $request): Response
    {
        $filters = [
            'log' => $request->string('log')->toString(),
            'actor' => trim($request->string('actor')->toString()),
            'from' => $request->string('from')->toString(),
            'to' => $request->string('to')->toString(),
        ];

        $records = Activity::query()
            ->with('causer')
            ->when($filters['log'] !== '', fn (Builder $query) => $query->where('log_name', $filters['log']))
            ->when($filters['actor'] !== '', fn (Builder $query) => $query
                ->where('causer_type', (new User)->getMorphClass())
                ->whereIn('causer_id', User::query()->select('id')->where(fn (Builder $user) => $user
                    ->whereRaw("concat(first_name, ' ', last_name) ilike ?", ['%'.$filters['actor'].'%'])
                    ->orWhere('email', 'ilike', '%'.$filters['actor'].'%'))))
            ->when($filters['from'] !== '', fn (Builder $query) => $query->whereDate('created_at', '>=', $filters['from']))
            ->when($filters['to'] !== '', fn (Builder $query) => $query->whereDate('created_at', '<=', $filters['to']))
            ->latest('id')
            ->paginate(50)
            ->withQueryString()
            ->through(fn (Activity $activity): array => [
                'id' => $activity->id,
                'log' => $activity->log_name,
                'log_label' => AuditLog::tryFrom((string) $activity->log_name)?->label() ?? (string) $activity->log_name,
                'event' => $activity->event,
                'description' => __((string) $activity->description),
                'subject' => $activity->subject_type ? __(class_basename($activity->subject_type)).' #'.$activity->subject_id : null,
                'actor' => $activity->causer instanceof User ? $activity->causer->name : null,
                'properties' => $activity->properties?->toArray() ?? [],
                'created_at' => $activity->created_at?->toIso8601String(),
            ]);

        return Inertia::render('audit/index', [
            'records' => $records,
            'filters' => $filters,
            'logs' => AuditLog::options(),
        ]);
    }
}
