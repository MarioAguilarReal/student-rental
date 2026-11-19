<?php

namespace App\Modules\Properties\Actions;

use App\Models\User;
use App\Modules\Properties\Enums\PropertyStatus;
use App\Modules\Properties\Events\PropertyPublished;
use App\Modules\Properties\Models\Property;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;

/**
 * HU-04.07 · RN-10: publishing (or resuming a paused property) shows exactly what is missing.
 */
class PublishProperty
{
    public function __construct(private CheckPublicationRequirements $requirements) {}

    public function handle(User $actor, Property $property): Property
    {
        $property->status->assertCanTransitionTo(PropertyStatus::Published);

        $missing = $this->requirements->missing($property);

        if ($missing !== []) {
            throw new BusinessRuleViolation('RN-10', __('Before publishing: :missing.', ['missing' => implode('; ', $missing)]));
        }

        $property->forceFill([
            'status' => PropertyStatus::Published,
            'pause_reason' => null,
            'published_at' => $property->published_at ?? now(),
        ])->save();

        PropertyPublished::dispatch($property, $actor);

        return $property;
    }
}
