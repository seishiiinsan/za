<?php

namespace App\Http\Controllers;

use App\Http\Resources\AlterResource;
use App\Models\Alter;
use App\Support\BlockList;
use App\Support\Front;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Recherche d'alters. Aucune suggestion de comptes : une recommandation
 * pourrait rapprocher deux alters d'un même système.
 */
class SearchController extends Controller
{
    public function index(Request $request, Front $front, BlockList $blocks): Response
    {
        $term = trim((string) $request->query('q', ''));
        $hidden = $blocks->hiddenFrom($front->current());

        $results = $term === '' ? collect() : Alter::query()
            ->searchable()
            ->where(fn ($query) => $query->where('name', 'like', "%{$term}%")
                ->orWhere('handle', 'like', "%{$term}%"))
            ->whereKeyNot($hidden->all())
            ->orderBy('name')
            ->limit(25)
            ->get();

        return Inertia::render('Search/Index', [
            'term' => $term,
            'results' => AlterResource::collection($results),
        ]);
    }
}
