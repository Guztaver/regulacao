<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'error' => 'Authentication required',
                'message' => 'You must be logged in to access this resource'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Check if user account is still active/valid
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'error' => 'Invalid user session',
                'message' => 'User session is invalid or expired'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Optionally check if user email is verified (commented out for now)
        // if (!$user->hasVerifiedEmail()) {
        //     return response()->json([
        //         'error' => 'Email verification required',
        //         'message' => 'You must verify your email address to access this resource'
        //     ], Response::HTTP_FORBIDDEN);
        // }

        return $next($request);
    }
}
