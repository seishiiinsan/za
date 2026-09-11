<?php

namespace App\Http\Controllers;

use App\Http\Resources\AlterResource;
use App\Models\Alter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Recherche d'alters. Aucune suggestion de comptes : une recommandation
 * pourrait rapprocher deux alters d'un même système.
 */
class SearchController extends Controller
{
    public function index(Request $request): Response
    {
        $term = trim((string) $request->query('q', ''));

        $results = $term === '' ? collect() : Alter::query()
            ->searchable()
            ->where(fn ($query) => $query->where('name', 'like', "%{$term}%")
                ->orWhere('handle', 'like', "%{$term}%"))
            ->orderBy('name')
            ->limit(25)
            ->get();

        return Inertia::render('Search/Index', [
            'term' => $term,
            'results' => AlterResource::collection($results),
        ]);
    }
}
