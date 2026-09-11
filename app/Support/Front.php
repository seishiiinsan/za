<?php

namespace App\Support;

use App\Models\Alter;
use Illuminate\Http\Request;

/**
 * Le « front actif » : l'alter sélectionné dans la session.
 * Session = system_id (auth) + active_alter_id (ici). Changer de front ne déconnecte pas.
 */
class Front
{
    public const SESSION_KEY = 'active_alter_id';

    protected ?Alter $resolved = null;

    /** Requête pour laquelle `$resolved` a été calculé (le service survit à la requête en test). */
    protected ?Request $resolvedFor = null;

    protected function request(): Request
    {
        return request();
    }

    public function current(): ?Alter
    {
        $request = $this->request();

        if ($this->resolvedFor === $request) {
            return $this->resolved;
        }

        $this->resolvedFor = $request;
        $this->resolved = null;

        $system = $request->user();
        $id = $request->hasSession() ? $request->session()->get(self::SESSION_KEY) : null;

        if ($system === null || $id === null) {
            return null;
        }

        // Toujours relire depuis les alters du système : une session ne peut pas
        // désigner un alter qui ne lui appartient pas.
        return $this->resolved = $system->alters()->find($id);
    }

    public function currentOrFail(): Alter
    {
        return $this->current() ?? abort(409, 'Aucun front actif.');
    }

    public function set(Alter $alter): void
    {
        $request = $this->request();
        $request->session()->put(self::SESSION_KEY, $alter->getKey());
        $this->resolvedFor = $request;
        $this->resolved = $alter;
    }

    public function clear(): void
    {
        $request = $this->request();
        $request->session()->forget(self::SESSION_KEY);
        $this->resolvedFor = $request;
        $this->resolved = null;
    }

    /** Sélectionne un front par défaut après connexion / création d'alter. */
    public function ensure(): ?Alter
    {
        if ($alter = $this->current()) {
            return $alter;
        }

        $first = $this->request()->user()?->alters()->oldest('id')->first();

        if ($first) {
            $this->set($first);
        }

        return $first;
    }
}
