<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../Layouts/AppLayout.vue'
</script>

<template>
  <Head title="Accueil" />
  <AppLayout>
    <section class="flex flex-col gap-6 py-10">
      <span class="font-display text-6xl text-accent">座</span>
      <h1 class="max-w-[18ch] font-display text-5xl leading-[1.05] text-balance">
        Un compte. Autant de voix qu'il en faut.
      </h1>
      <p class="max-w-[55ch] text-lg leading-relaxed text-muted">
        Za est un réseau social pour les systèmes pluriels. Chaque alter a son profil, ses
        abonnements et ses messages. Rien, nulle part, ne permet de relier publiquement deux
        profils au même compte.
      </p>
      <div class="flex flex-wrap gap-3">
        <Link href="/register" class="za-btn px-6 py-3">Créer un système</Link>
        <Link href="/login" class="za-btn-ghost px-6 py-3">Se connecter</Link>
      </div>

      <dl class="mt-6 grid gap-4 sm:grid-cols-3">
        <div class="za-card p-5">
          <dt class="text-sm font-semibold">Le front, toujours visible</dt>
          <dd class="mt-1.5 text-sm leading-relaxed text-muted">
            L'alter qui écrit reste à l'écran. Changer de front ne déconnecte pas.
          </dd>
        </div>
        <div class="za-card p-5">
          <dt class="text-sm font-semibold">Rien ne relie deux alters</dt>
          <dd class="mt-1.5 text-sm leading-relaxed text-muted">
            Identifiants opaques, aucune suggestion de comptes, listes masquées par défaut.
          </dd>
        </div>
        <div class="za-card p-5">
          <dt class="text-sm font-semibold">Le consentement d'abord</dt>
          <dd class="mt-1.5 text-sm leading-relaxed text-muted">
            Un post à plusieurs n'est publié que si tous ses auteurs ont accepté.
          </dd>
        </div>
      </dl>
    </section>
  </AppLayout>
</template>
