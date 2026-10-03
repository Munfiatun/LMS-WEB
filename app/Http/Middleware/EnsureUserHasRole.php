<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if (! $user->is_active) {
            auth()->logout();

            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda sedang dinonaktifkan oleh administrator.',
            ]);
        }

        if (! empty($roles) && ! $user->hasRole(...$roles)) {
            // Redirect user to their appropriate dashboard if they access wrong role route
            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard')->with('error', 'Akses ditolak: Anda dialihkan ke dashboard Anda.');
            }
            if ($user->isInstructor()) {
                return redirect()->route('instructor.dashboard')->with('error', 'Akses ditolak: Anda dialihkan ke dashboard Anda.');
            }

            return redirect()->route('student.dashboard')->with('error', 'Akses ditolak: Anda dialihkan ke dashboard Anda.');
        }

        return $next($request);
    }
}
