<?php

namespace App\Modules\Shared\Http;

use Inertia\Inertia;

/**
 * One-off toast messages for the next Inertia response (rendered by use-flash-toast.ts).
 */
final class Toast
{
    public static function success(string $message): void
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);
    }

    public static function error(string $message): void
    {
        Inertia::flash('toast', ['type' => 'error', 'message' => $message]);
    }
}
