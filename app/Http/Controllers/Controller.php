<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /** Abort with 403 unless the current user holds the given permission. */
    protected function authorizeAbility(string $ability): void
    {
        abort_unless(request()->user()?->can($ability), 403);
    }
}
