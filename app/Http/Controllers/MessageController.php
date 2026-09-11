<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Support\Front;
use App\Support\Messaging;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(protected Front $front, protected Messaging $messaging) {}

    public function store(Request $request, Conversation $conversation): RedirectResponse
    {
        $data = $request->validate([
            'content' => ['required', 'string', 'max:4000'],
        ]);

        $alter = $this->front->currentOrFail();
        $mine = $this->messaging->correspondentFor($alter);

        abort_unless($conversation->participants->contains($mine), 404);

        $this->messaging->send($conversation, $alter, $data['content']);

        return back();
    }
}
