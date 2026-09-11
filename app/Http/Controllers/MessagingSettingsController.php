<?php

namespace App\Http\Controllers;

use App\Enums\MessagingMode;
use App\Support\Messaging;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Réglages de messagerie, au niveau système.
 *
 * La surface publique du système se limite à un nom et une description. Elle
 * n'expose jamais ses alters : c'est la seule surface système du produit.
 */
class MessagingSettingsController extends Controller
{
    public function __construct(protected Messaging $messaging) {}

    public function edit(Request $request): Response
    {
        $system = $request->user();

        return Inertia::render('Settings/Messaging', [
            'settings' => [
                'mode' => $this->messaging->modeOf($system)->value,
                'display_name' => $system->display_name,
                'description' => $system->description,
                'show_message_author' => $this->messaging->showsAuthor($system),
            ],
            'modes' => array_map(
                fn (MessagingMode $mode) => ['value' => $mode->value, 'label' => $mode->label()],
                MessagingMode::cases()
            ),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $system = $request->user();

        $data = $request->validate([
            'mode' => ['required', Rule::enum(MessagingMode::class)],
            'display_name' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:500'],
            'show_message_author' => ['boolean'],
        ]);

        $system->update([
            'display_name' => $data['display_name'] ?? null,
            'description' => $data['description'] ?? null,
            'settings' => array_merge($system->settings ?? [], [
                'messaging_mode' => $data['mode'],
                'show_message_author' => (bool) ($data['show_message_author'] ?? false),
            ]),
        ]);

        return back()->with('status', 'Réglages de messagerie enregistrés.');
    }
}
