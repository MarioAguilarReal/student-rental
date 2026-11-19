<?php

namespace App\Modules\Pricing\Actions;

use App\Modules\Pricing\Models\AvailabilityBlock;
use App\Modules\Properties\Actions\EnsurePropertyIsEditable;

class RemoveAvailabilityBlock
{
    public function handle(AvailabilityBlock $block): void
    {
        EnsurePropertyIsEditable::check($block->unit->property);

        $block->delete();
    }
}
