<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../Layouts/AppLayout.vue'
</script>

<template>
  <Head title="Accueil" />
  <AppLayout>
    <section class="space-y-4 py-10">
      <h1 class="text-3xl font-semibold tracking-tight">Za — 座</h1>
      <p class="max-w-prose text-neutral-400">
        Un compte = un système. Chaque alter a son profil public. Rien ne relie publiquement
        deux alters d'un même système.
      </p>
      <div class="flex gap-3">
        <Link href="/register" class="rounded-md bg-violet-600 px-4 py-2 text-sm font-medium hover:bg-violet-500">
          Créer un système
        </Link>
        <Link href="/login" class="rounded-md border border-neutral-800 px-4 py-2 text-sm hover:border-neutral-600">
          Se connecter
        </Link>
      </div>
    </section>
  </AppLayout>
</template>
