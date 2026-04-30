<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * //closure est une fonction anonyme qui prend une requete et retourne une reponse, 
     * c'est la signature de la fonction handle 
     * la fonction handle est appelée pour chaque requete entrante, elle vérifie si l'utilisateur a le rôle requis pour accéder à la ressource demandée
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        foreach ($roles as $role) {
            if ($request->user()->hasRole($role)) {
                return $next($request);
            }
        }

        return response()->json(['error' => 'Forbidden - Insufficient role'], 403);
    }
}
