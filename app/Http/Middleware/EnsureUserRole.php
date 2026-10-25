<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Cek status aktif akun
        if (isset($user->status_aktif) && ! $user->status_aktif) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Akun Anda telah dinonaktifkan oleh administrator.');
        }

        if ($user->role !== $role) {
            // Redirect ke dashboard masing-masing jika peran tidak cocok
            if ($user->role === 'admin') {
                return redirect()->route('admin.dashboard')->with('error', 'Halaman teknisi tidak dapat diakses oleh admin.');
            }

            if ($user->role === 'teknisi') {
                return redirect()->route('teknisi.dashboard')->with('error', 'Halaman admin tidak dapat diakses oleh teknisi.');
            }

            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk halaman ini.');
        }

        return $next($request);
    }
}
