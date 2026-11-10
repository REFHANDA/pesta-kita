<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainTextToken = $request->bearerToken();

        if ($plainTextToken === null) {
            abort(401, 'Unauthenticated.');
        }

        $token = ApiToken::query()
            ->where('token_hash', hash('sha256', $plainTextToken))
            ->with('user')
            ->first();

        if ($token === null) {
            abort(401, 'Unauthenticated.');
        }

        $token->forceFill(['last_used_at' => now()])->save();
        $request->attributes->set('api_token', $token);
        $request->setUserResolver(fn () => $token->user);

        return $next($request);
    }
}
