<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Symfony\Component\HttpFoundation\Response;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        $user = $request->user();

        if ($user !== null && $user->hasRole('member') && ! $user->hasAnyRole(['superadmin', 'organization_admin'])) {
            return redirect()->intended('/inventory');
        }

        return redirect()->intended('/dashboard');
    }
}
