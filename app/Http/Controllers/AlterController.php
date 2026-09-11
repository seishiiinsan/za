<?php

namespace App\Http\Controllers;

use App\Enums\PrivacyLevel;
use App\Http\Resources\AlterResource;
use App\Models\Alter;
use App\Support\AvatarStorage;
use App\Support\Front;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AlterController extends Controller
{
    public function __construct(protected Front $front, protected AvatarStorage $avatars) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Alters/Index', [
            'alters' => AlterResource::collection($request->user()->alters()->orderBy('name')->get()),
            'trashed' => AlterResource::collection(
                $request->user()->alters()->onlyTrashed()->orderBy('name')->get()
            ),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Alters/Form', [
            'alter' => null,
            'privacyLevels' => $this->privacyLevels(),
        ]);
    }

    public function store(Request $request, Front $front): RedirectResponse
    {
        $data = $this->validated($request);
        $alter = $request->user()->alters()->create($this->withAvatar($request, $data));

        // Premier alter : il devient le front actif immédiatement.
        if ($front->current() === null) {
            $front->set($alter);
        }

        return redirect()->route('alters.index')->with('status', "Alter « {$alter->name} » créé.");
    }

    public function edit(Request $request, Alter $alter): Response
    {
        $this->authorizeAlter($request, $alter);

        return Inertia::render('Alters/Form', [
            'alter' => (new AlterResource($alter))->resolve() + [
                'show_connections' => $alter->showsConnections(),
            ],
            'privacyLevels' => $this->privacyLevels(),
        ]);
    }

    public function update(Request $request, Alter $alter): RedirectResponse
    {
        $this->authorizeAlter($request, $alter);

        $alter->update($this->withAvatar($request, $this->validated($request, $alter)));

        return redirect()->route('alters.index')->with('status', 'Alter mis à jour.');
    }

    public function destroy(Request $request, Alter $alter): RedirectResponse
    {
        $this->authorizeAlter($request, $alter);

        // Suppression douce : l'alter disparaît de toutes les surfaces (profil,
        // recherche, feed, listes) mais reste restaurable. Ses posts existants
        // s'affichent sous un auteur anonyme.
        if ($this->front->current()?->is($alter)) {
            $this->front->clear();
        }

        $alter->delete();
        $this->front->ensure();

        return redirect()->route('alters.index')
            ->with('status', "Alter « {$alter->name} » supprimé. Il reste restaurable.");
    }

    public function restore(Request $request, string $uuid): RedirectResponse
    {
        $alter = $request->user()->alters()->onlyTrashed()->where('uuid', $uuid)->firstOrFail();

        $alter->restore();

        return redirect()->route('alters.index')->with('status', "Alter « {$alter->name} » restauré.");
    }

    protected function authorizeAlter(Request $request, Alter $alter): void
    {
        abort_unless($alter->system_id === $request->user()->getKey(), 404);
    }

    /** @return array<string, mixed> */
    protected function validated(Request $request, ?Alter $alter = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'handle' => [
                'required', 'string', 'max:30', 'regex:/^[a-z0-9_]+$/',
                Rule::unique('alters', 'handle')->ignore($alter),
            ],
            'pronouns' => ['nullable', 'string', 'max:40'],
            'bio' => ['nullable', 'string', 'max:500'],
            'privacy_level' => ['required', Rule::enum(PrivacyLevel::class)],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'show_connections' => ['boolean'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function withAvatar(Request $request, array $data): array
    {
        if ($request->hasFile('avatar')) {
            $data['avatar_path'] = $this->avatars->store($request->file('avatar'));
        }

        // Listes followers/abonnements masquées par défaut (anti-corrélation par graphe).
        $data['settings'] = ['show_connections' => (bool) ($data['show_connections'] ?? false)];

        unset($data['avatar'], $data['show_connections']);

        return $data;
    }

    /** @return array<int, array{value: string, label: string}> */
    protected function privacyLevels(): array
    {
        return array_map(
            fn (PrivacyLevel $level) => [
                'value' => $level->value,
                'label' => $level->label(),
                'description' => $level->description(),
            ],
            PrivacyLevel::cases()
        );
    }
}
