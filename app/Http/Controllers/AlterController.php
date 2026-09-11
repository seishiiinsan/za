<?php

namespace App\Http\Controllers;

use App\Enums\PrivacyLevel;
use App\Http\Resources\AlterResource;
use App\Models\Alter;
use App\Support\AvatarStorage;
use App\Support\Front;
use App\Support\Handles;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AlterController extends Controller
{
    public function __construct(
        protected Front $front,
        protected AvatarStorage $avatars,
        protected Handles $handles,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Alters/Index', [
            'alters' => AlterResource::collection($request->user()->alters()->orderBy('name')->get()),
            // Vue privée du système : ses propres alters supprimés gardent
            // leur nom, contrairement aux surfaces publiques.
            'trashed' => $request->user()->alters()->onlyTrashed()->orderBy('name')->get()
                ->map(fn (Alter $alter) => [
                    'id' => $alter->uuid,
                    'name' => $alter->name,
                    'previous_handle' => $alter->settings['handle_before_delete'] ?? null,
                    'handle_available' => ($previous = $alter->settings['handle_before_delete'] ?? null) !== null
                        && $this->handles->isAvailableFor($previous, $alter),
                ])->values(),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Alters/Form', [
            'alter' => null,
            'siblings' => AlterResource::collection($request->user()->alters()->orderBy('name')->get()),
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
                'notify_system' => $alter->notifiesSystem(),
                'delegate_to' => $alter->delegate()?->uuid,
                'color' => $alter->colorIndex(),
            ],
            'siblings' => AlterResource::collection(
                $request->user()->alters()->whereKeyNot($alter->getKey())->orderBy('name')->get()
            ),
            'privacyLevels' => $this->privacyLevels(),
        ]);
    }

    public function update(Request $request, Alter $alter): RedirectResponse
    {
        $this->authorizeAlter($request, $alter);

        $data = $this->withAvatar($request, $this->validated($request, $alter));
        $previous = $alter->handle;

        if ($this->handles->normalize($data['handle']) !== $alter->handle_key) {
            $data['handle_changed_at'] = now();
        }

        $alter->forceFill($data)->save();

        if ($previous !== $alter->handle) {
            // L'ancien handle ne repart pas dans le stock tout de suite : le
            // reprendre permettrait de se faire passer pour l'alter.
            $this->handles->quarantine($previous, $alter);
        }

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

        $alter->forceFill([
            'settings' => array_merge($alter->settings ?? [], ['handle_before_delete' => $alter->handle]),
            'handle' => $this->handles->placeholderFor($alter),
        ])->save();

        $alter->delete();
        $this->front->ensure();

        return redirect()->route('alters.index')->with(
            'status',
            "Alter « {$alter->name} » supprimé. Son handle @{$alter->settings['handle_before_delete']} est de nouveau libre."
        );
    }

    public function restore(Request $request, string $uuid): RedirectResponse
    {
        $alter = $request->user()->alters()->onlyTrashed()->where('uuid', $uuid)->firstOrFail();
        $wanted = $request->input('handle', $alter->settings['handle_before_delete'] ?? null);

        if ($wanted === null
            || $this->handles->isReservedWord($wanted)
            || ! $this->handles->matchesFormat($wanted)
            || ! $this->handles->isAvailableFor($wanted, $alter)) {
            return back()->with(
                'error',
                "Le handle @{$wanted} n'est plus disponible : choisissez-en un autre pour restaurer « {$alter->name} »."
            );
        }

        $alter->forceFill(['handle' => $wanted])->save();
        $alter->restore();

        return redirect()->route('alters.index')->with('status', "Alter « {$alter->name} » restauré sous @{$wanted}.");
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
                'required', 'string',
                'min:'.Handles::MIN_LENGTH, 'max:'.Handles::MAX_LENGTH,
                'regex:/^[a-z0-9_]+$/',
                function (string $attribute, mixed $value, Closure $fail) use ($alter) {
                    if ($this->handles->isReservedWord($value)) {
                        $fail('Ce handle est réservé.');

                        return;
                    }

                    if (! $this->handles->isAvailableFor($value, $alter)) {
                        // Même message qu'un handle pris : inutile de dire si
                        // c'est une quarantaine ou un alter existant.
                        $fail('Ce handle est déjà utilisé.');

                        return;
                    }

                    if ($alter !== null
                        && $this->handles->normalize($value) !== $alter->handle_key
                        && ! $this->handles->canChangeHandle($alter)) {
                        $fail(sprintf(
                            'Changement possible à partir du %s : un handle ne change pas plus d\'une fois tous les %d jours.',
                            $this->handles->nextChangeAllowedAt($alter)->format('d/m/Y'),
                            Handles::COOLDOWN_DAYS,
                        ));
                    }
                },
            ],
            'pronouns' => ['nullable', 'string', 'max:40'],
            'bio' => ['nullable', 'string', 'max:500'],
            'privacy_level' => ['required', Rule::enum(PrivacyLevel::class)],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'show_connections' => ['boolean'],
            'notify_system' => ['boolean'],
            'color' => ['nullable', 'integer', 'min:0', 'max:5'],
            'delegate_to' => ['nullable', 'uuid'],
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
        $data['settings'] = [
            'show_connections' => (bool) ($data['show_connections'] ?? false),
            'notify_system' => (bool) ($data['notify_system'] ?? false),
            'color' => $data['color'] ?? null,
            // La délégation ne vaut qu'entre alters d'un même système : elle
            // reste privée et ne crée aucun lien public.
            'delegate_to' => $this->delegateUuid($request, $data['delegate_to'] ?? null),
        ];

        unset($data['avatar'], $data['show_connections'], $data['notify_system'], $data['delegate_to'], $data['color']);

        return $data;
    }

    /** Un alter ne délègue qu'à un autre alter du même système. */
    protected function delegateUuid(Request $request, ?string $uuid): ?string
    {
        if ($uuid === null) {
            return null;
        }

        return $request->user()->alters()->where('uuid', $uuid)->exists() ? $uuid : null;
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
