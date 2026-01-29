<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        // 🔥 HANDLE FILE TERLALU BESAR
        $this->renderable(function (PostTooLargeException $e, $request) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Ukuran file terlalu besar. Maksimal upload 2MB.');
        });
    }
}
