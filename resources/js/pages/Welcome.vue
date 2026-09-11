<script setup>
import { Head } from '@inertiajs/vue3'
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
    </section>
  </AppLayout>
</template>
