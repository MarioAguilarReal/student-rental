<?php

namespace App\Modules\Properties\Actions;

use App\Modules\Properties\Enums\GenderPolicy;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Models\Unit;
use App\Modules\Shared\Exceptions\BusinessRuleViolation;

/**
 * RN-30 / RN-31: walking the tree from the root, every unit may only keep or tighten the
 * gender policy and the allowed communities it inherits. Called after any change to the
 * property's rules or to a unit, inside the same transaction.
 */
class ValidateCoexistenceRules
{
    public function handle(Property $property): void
    {
        $units = $property->units()->get();

        foreach ($units->whereNull('parent_id') as $root) {
            $this->check($root, array_values($units->all()), $property->gender_policy, $property->allowed_communities);
        }
    }

    /**
     * @param  list<Unit>  $all
     * @param  list<string>  $inheritedCommunities
     */
    private function check(Unit $unit, array $all, GenderPolicy $inheritedPolicy, array $inheritedCommunities): void
    {
        $policy = $unit->gender_policy ?? $inheritedPolicy;
        $communities = $unit->allowed_communities ?? $inheritedCommunities;

        if (! $inheritedPolicy->allowsChild($policy)) {
            throw new BusinessRuleViolation('RN-30', __(':unit cannot be less restrictive than the space it belongs to (:policy).', [
                'unit' => $unit->name,
                'policy' => $inheritedPolicy->label(),
            ]), 'gender_policy');
        }

        $loosens = $inheritedCommunities !== []
            && ($communities === [] || array_diff($communities, $inheritedCommunities) !== []);

        if ($loosens) {
            throw new BusinessRuleViolation('RN-31', __(':unit cannot admit communities that the space it belongs to does not admit.', [
                'unit' => $unit->name,
            ]), 'allowed_communities');
        }

        foreach ($all as $child) {
            if ($child->parent_id === $unit->id) {
                $this->check($child, $all, $policy, $communities);
            }
        }
    }
}
