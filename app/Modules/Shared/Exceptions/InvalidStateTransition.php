<?php

namespace App\Modules\Shared\Exceptions;

use BackedEnum;

class InvalidStateTransition extends BusinessRuleViolation
{
    public function __construct(
        public readonly BackedEnum $from,
        public readonly BackedEnum $to,
    ) {
        parent::__construct(
            rule: 'state-transition',
            message: __('The transition from :from to :to is not allowed.', [
                'from' => self::describe($from),
                'to' => self::describe($to),
            ]),
            field: 'status',
        );
    }

    private static function describe(BackedEnum $state): string
    {
        return method_exists($state, 'label') ? (string) $state->label() : (string) $state->value;
    }
}
