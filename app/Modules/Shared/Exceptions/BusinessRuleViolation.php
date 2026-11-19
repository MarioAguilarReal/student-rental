<?php

namespace App\Modules\Shared\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Thrown by Actions when a business rule (RN-xx in docs/spec/03-reglas-negocio.md) is violated.
 *
 * It is an expected outcome, not a bug: it is not reported and it is shown to the user
 * as a validation error under `$field` (Inertia) or as a 422 response (JSON).
 */
class BusinessRuleViolation extends RuntimeException implements ShouldntReport
{
    public function __construct(
        public readonly string $rule,
        string $message,
        public readonly string $field = 'domain',
    ) {
        parent::__construct($message);
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'rule' => $this->rule,
                'errors' => [$this->field => [$this->getMessage()]],
            ], 422);
        }

        return back()->withInput()->withErrors([$this->field => $this->getMessage()]);
    }
}
