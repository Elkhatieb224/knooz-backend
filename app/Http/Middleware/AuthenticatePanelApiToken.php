<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use App\Models\CenterUser;
use App\Models\CompanyUser;
use App\Models\PanelApiToken;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatePanelApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->bearerToken();

        if (! $header) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token = PanelApiToken::findValidToken($header);

        if (! $token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token->forceFill(['last_used_at' => now()])->save();
        $tokenable = $token->tokenable;

        if (! $tokenable) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $panel = match (true) {
            $tokenable instanceof AdminUser => 'admin',
            $tokenable instanceof CompanyUser => 'company',
            $tokenable instanceof CenterUser => 'center',
            $tokenable instanceof User => 'mobile',
            default => 'mobile',
        };

        // Prefer the panel stored on the token when present.
        if (is_string($token->panel) && $token->panel !== '') {
            $panel = $token->panel;
        }

        $request->setUserResolver(fn () => $tokenable);
        $request->attributes->set('panel', $panel);
        $request->attributes->set('panel_api_token', $token);
        $request->attributes->set('panel_api_token_plain', $header);

        return $next($request);
    }
}
