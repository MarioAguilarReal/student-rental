<?php

namespace App\Modules\Shared\Enums\Concerns;

use App\Modules\Shared\Exceptions\InvalidStateTransition;

/**
 * For status enums: each case declares which states it may move to.
 * Actions must call assertCanTransitionTo() before changing a status.
 *
 * @phpstan-require-implements \BackedEnum
 */
trait HasTransitions
{
    /**
     * @return list<self>
     */
    abstract public function allowedTransitions(): array;

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    public function assertCanTransitionTo(self $to): void
    {
        if (! $this->canTransitionTo($to)) {
            throw new InvalidStateTransition($this, $to);
        }
    }
}
