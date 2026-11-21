<?php

namespace App\Modules\Notifications\Listeners;

use App\Modules\Inspections\Events\InspectionAssigned;
use App\Modules\Inspections\Events\InspectionCompleted;
use App\Modules\Inspections\Events\InspectionRenewalOpened;
use App\Modules\Notifications\Messages\PlatformMessage;
use App\Modules\Notifications\Support\Recipients;
use Illuminate\Support\Facades\Notification;

/**
 * HU-10.01: inspection assigned → inspector and owner; completed → owner and administrators;
 * about to expire (renewal opened) → owner and administrators.
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

        Notification::send(Recipients::admins(), new PlatformMessage(
            $approved ? 'inspection.approved' : 'inspection.rejected',
            $approved ? ':property passed its inspection' : ':property did not pass its inspection',
            'Result recorded by :inspector.',
            'View inspection',
            route('inspections.queue.show', $inspection, absolute: false),
            ['property' => $property->title, 'inspector' => (string) $inspection->inspector?->name],
        ));
    }

    public function renewalOpened(InspectionRenewalOpened $event): void
    {
        $inspection = $event->inspection;
        $property = $inspection->property;
        $replace = ['property' => $property->title, 'date' => (string) $property->inspection_valid_until?->toDateString()];

        $property->owner->notify(new PlatformMessage(
            'inspection.expiring',
            'The inspection of :property expires on :date',
            'We opened its renewal. An administrator will schedule the visit; if it is not approved in time, the property leaves the catalog.',
            'View property',
            route('properties.edit', $property, absolute: false).'#publication',
            $replace,
        ));

        Notification::send(Recipients::admins(), new PlatformMessage(
            'inspection.expiring',
            'The inspection of :property expires on :date',
            'A renewal is waiting for an inspector and a date.',
            'Assign inspection',
            route('inspections.queue.show', $inspection, absolute: false),
            $replace,
        ));
    }
}
