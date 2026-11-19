<?php

namespace App\Modules\Inspections\Listeners;

use App\Modules\Inspections\Actions\OpenInspection;
use App\Modules\Inspections\Enums\InspectionType;
use App\Modules\Properties\Events\PropertySubmittedForInspection;

/**
 * HU-04.06 → HU-06.01: a property sent to inspection appears in the administrators' queue.
 * Runs after the property's transaction commits (the event is dispatched after commit).
 */
class OpenInspectionWhenRequested
{
    public function __construct(private OpenInspection $openInspection) {}

    public function handle(PropertySubmittedForInspection $event): void
    {
        $this->openInspection->handle(
            $event->property,
            $event->reason === 'change' ? InspectionType::Change : InspectionType::Initial,
        );
    }
}
