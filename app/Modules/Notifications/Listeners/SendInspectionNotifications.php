<?php

namespace App\Modules\Notifications\Listeners;

use App\Modules\Inspections\Events\InspectionAssigned;
use App\Modules\Inspections\Events\InspectionCompleted;
use App\Modules\Notifications\Messages\PlatformMessage;

/**
 * HU-10.01: inspection assigned → inspector and owner; inspection completed → owner.
 */
class SendInspectionNotifications
{
    public function assigned(InspectionAssigned $event): void
    {
        $inspection = $event->inspection;
        $property = $inspection->property;
        $date = (string) $inspection->scheduled_for?->toDateString();

        $inspection->inspector?->notify(new PlatformMessage(
            'inspection.assigned',
            'New inspection on :date',
            'You will inspect :property on :date.',
            'Open inspection',
            route('inspections.perform', $inspection, absolute: false),
            ['property' => $property->title, 'date' => $date],
        ));

        $property->owner->notify(new PlatformMessage(
            'inspection.scheduled',
            'Your inspection is scheduled for :date',
            'An inspector will visit :property on :date.',
            'View property',
            route('properties.edit', $property, absolute: false).'#publication',
            ['property' => $property->title, 'date' => $date],
        ));
    }

    public function completed(InspectionCompleted $event): void
    {
        $inspection = $event->inspection;
        $property = $inspection->property;
        $approved = (bool) $inspection->result?->approves();

        $property->owner->notify(new PlatformMessage(
            $approved ? 'inspection.approved' : 'inspection.rejected',
            $approved ? ':property passed its inspection' : ':property did not pass its inspection',
            $approved ? 'You can publish it once it has prices.' : 'Read the observations, fix them and request a new inspection.',
            'View result',
            route('properties.edit', $property, absolute: false).'#publication',
            ['property' => $property->title],
        ));
    }
}
