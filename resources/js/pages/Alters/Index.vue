<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'

defineProps({
  alters: { type: Object, required: true },
  trashed: { type: Object, required: true },
})

function destroy(alter) {
  if (confirm(`Supprimer « ${alter.name} » ? Il disparaît de toutes les surfaces, mais reste restaurable.`)) {
    router.delete(`/alters/${alter.id}`)
  }
}

function restore(alter) {
  router.post(`/alters/${alter.id}/restore`)
}
</script>

<template>
  <Head title="Alters" />
  <AppLayout>
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-semibold">Alters</h1>
      <Link href="/alters/create" class="rounded-md bg-violet-600 px-3 py-1.5 text-sm font-medium hover:bg-violet-500">
        Nouvel alter
      </Link>
    </div>

    <ul class="space-y-2">
      <li
        v-for="alter in alters.data"
        :key="alter.id"
        class="flex items-center gap-3 rounded-lg border border-neutral-800 bg-neutral-900 p-3"
      >
        <img v-if="alter.avatar_url" :src="alter.avatar_url" alt="" class="h-10 w-10 rounded-full object-cover" />
        <div v-else class="h-10 w-10 rounded-full bg-neutral-800" />
        <div class="min-w-0">
          <p class="truncate font-medium">{{ alter.name }}</p>
          <p class="truncate text-xs text-neutral-500">
            @{{ alter.handle }} · {{ alter.is_private ? 'privé' : 'public' }}
          </p>
        </div>
        <div class="ml-auto flex gap-3 text-xs">
          <Link :href="`/@${alter.handle}`" class="text-neutral-400 hover:text-neutral-100">Profil</Link>
          <Link :href="`/alters/${alter.id}/edit`" class="text-neutral-400 hover:text-neutral-100">Éditer</Link>
          <button class="text-neutral-500 hover:text-red-400" @click="destroy(alter)">Supprimer</button>
        </div>
      </li>
    </ul>

    <p v-if="!alters.data.length" class="text-sm text-neutral-500">Aucun alter pour l'instant.</p>

    <section v-if="trashed.data.length" class="space-y-2">
      <h2 class="text-sm uppercase tracking-wide text-neutral-500">Supprimés</h2>
      <p class="text-xs text-neutral-600">
        Invisibles partout : profil, recherche, feed, listes d'abonnements. Restaurables tels quels.
      </p>
      <div
        v-for="alter in trashed.data"
        :key="alter.id"
        class="flex items-center gap-3 rounded-lg border border-dashed border-neutral-800 p-3 text-sm text-neutral-500"
      >
        <span>Alter supprimé</span>
        <button class="ml-auto text-xs text-violet-400 hover:text-violet-300" @click="restore(alter)">
          Restaurer
        </button>
      </div>
    </section>
  </AppLayout>
</template>
