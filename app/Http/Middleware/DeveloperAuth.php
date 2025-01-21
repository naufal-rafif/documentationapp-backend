<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DeveloperAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->cookie('authentication_id')) {
            $user_id = decrypt($request->cookie('authentication_id'));
            $users = User::where('id', $user_id)->first();
            if ($users->roles->pluck('name')->toArray()[0] === 'Developer') {
                return $next($request);
            }
        }

        return redirect()->intended('documentation')->with('error', 'Opps, You\'re not Admin');
    }
}
