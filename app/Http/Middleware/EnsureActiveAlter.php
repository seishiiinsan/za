<?php

namespace App\Http\Middleware;

use App\Support\Front;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garde-fou : aucune écriture publique sans front actif.
 */
class EnsureActiveAlter
{
    public function __construct(protected Front $front) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->front->ensure() === null) {
            return redirect()
                ->route('alters.create')
                ->with('error', 'Crée un alter et sélectionne un front avant de publier.');
        }

        return $next($request);
    }
}
