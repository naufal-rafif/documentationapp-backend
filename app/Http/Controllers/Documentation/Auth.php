<?php

namespace App\Http\Controllers\Documentation;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as AuthFacades;

class Auth extends Controller
{
    public function index(Request $request)
    {
        if ($request->cookie('authentication_id')) {
            $user_id = decrypt($request->cookie('authentication_id'));
            $users = User::where('id', $user_id)->first();
            if ($users &&$users->roles->pluck('name')->toArray()[0] === 'Developer') {
                return redirect()->intended('api/documentation');
            }
        }

        return view('login');
    }

    public function login(Request $request)
    {
        if (
            AuthFacades::guard('api')->setTTL(60)->attempt([
                'email' => $request->email,
                'password' => $request->password,
            ])
        ) {
            $cookieValue = encrypt(AuthFacades::guard('api')->user()->id);
            $cookieDuration = 60;

            $cookie = cookie('authentication_id', $cookieValue, $cookieDuration);

            return redirect()->intended('api/documentation')->withCookie($cookie);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout()
    {
        $cookieName = 'authentication_id';

        $cookie = cookie($cookieName, null, -1);

        return redirect()->intended('documentation')->withCookie($cookie);
    }
}
