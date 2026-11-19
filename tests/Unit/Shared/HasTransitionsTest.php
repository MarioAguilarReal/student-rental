<?php

use App\Modules\Shared\Enums\Concerns\HasTransitions;
use App\Modules\Shared\Exceptions\InvalidStateTransition;

enum TransitionsFixtureStatus: string
{
    use HasTransitions;

    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Published, self::Archived],
            self::Published => [self::Archived],
            self::Archived => [],
        };
    }
}

it('allows declared transitions', function () {
    expect(TransitionsFixtureStatus::Draft->canTransitionTo(TransitionsFixtureStatus::Published))->toBeTrue();

    TransitionsFixtureStatus::Published->assertCanTransitionTo(TransitionsFixtureStatus::Archived);
});

it('rejects undeclared transitions', function () {
    expect(TransitionsFixtureStatus::Archived->canTransitionTo(TransitionsFixtureStatus::Draft))->toBeFalse();

    TransitionsFixtureStatus::Archived->assertCanTransitionTo(TransitionsFixtureStatus::Draft);
})->throws(InvalidStateTransition::class);
