<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'

defineProps({
  alters: { type: Object, required: true },
  systemBlocks: { type: Array, required: true },
})

function unblock(id) {
  router.delete(`/blocks/${id}`, { preserveScroll: true })
}
</script>

<template>
  <Head title="Blocages" />
  <AppLayout>
    <h1 class="text-xl font-semibold">Blocages du front actif</h1>

    <section class="space-y-2">
      <h2 class="text-sm uppercase tracking-wide text-neutral-500">Alters bloqués</h2>
      <p class="text-xs text-neutral-600">
        Ne masque que cet alter. Les autres profils de la même personne continuent de vous voir.
      </p>
      <div
        v-for="alter in alters.data"
        :key="alter.id"
        class="flex items-center gap-3 rounded-lg border border-neutral-800 bg-neutral-900 p-3 text-sm"
      >
        <Link :href="`/@${alter.handle}`" class="hover:text-violet-400">
          {{ alter.name }} <span class="text-neutral-600">@{{ alter.handle }}</span>
        </Link>
      </div>
      <p v-if="!alters.data.length" class="text-sm text-neutral-600">Aucun.</p>
    </section>

    <section class="space-y-2">
      <h2 class="text-sm uppercase tracking-wide text-neutral-500">Comptes bloqués</h2>
      <p class="text-xs text-neutral-600">
        Masque tous les profils du compte, y compris ceux que vous ne connaissez pas. Za ne vous
        dit pas lesquels : ce serait vous révéler la personne derrière.
      </p>
      <div
        v-for="block in systemBlocks"
        :key="block.id"
        class="flex items-center gap-3 rounded-lg border border-neutral-800 bg-neutral-900 p-3 text-sm text-neutral-400"
      >
        <span>Compte bloqué le {{ new Date(block.blocked_at).toLocaleDateString() }}</span>
        <button class="ml-auto text-xs text-neutral-500 hover:text-violet-400" @click="unblock(block.id)">
          Débloquer
        </button>
      </div>
      <p v-if="!systemBlocks.length" class="text-sm text-neutral-600">Aucun.</p>
    </section>
  </AppLayout>
</template>
