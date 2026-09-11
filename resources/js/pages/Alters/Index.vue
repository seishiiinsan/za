<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Avatar from '../../Components/Avatar.vue'

defineProps({
  alters: { type: Object, required: true },
  trashed: { type: Array, required: true },
})

function destroy(alter) {
  if (confirm(`Supprimer « ${alter.name} » ? Il disparaît de toutes les surfaces, mais reste restaurable.`)) {
    router.delete(`/alters/${alter.id}`)
  }
}

function restore(alter) {
  // Le handle est libéré dès la suppression : il peut avoir été repris entre-temps.
  const handle = alter.handle_available
    ? alter.previous_handle
    : prompt(`@${alter.previous_handle} n'est plus libre. Nouveau handle pour ${alter.name} :`)

  if (handle) {
    router.post(`/alters/${alter.id}/restore`, { handle })
  }
}
</script>

<template>
  <Head title="Mes alters" />
  <AppLayout>
    <div class="flex flex-col gap-6">
      <header class="flex flex-wrap items-center gap-3">
        <h1 class="font-display text-3xl">Mes alters</h1>
        <Link href="/alters/create" class="za-btn ml-auto">Nouvel alter</Link>
      </header>

      <div class="flex flex-col gap-3">
        <div v-for="alter in alters.data" :key="alter.id" class="za-card flex flex-wrap items-center gap-3 p-4">
          <Avatar :alter="alter" :size="48" />
          <div class="min-w-0 flex-1">
            <p class="truncate font-semibold">{{ alter.name }}</p>
            <p class="truncate text-sm text-faint">
              @{{ alter.handle }} · {{ alter.is_private ? 'privé' : alter.privacy_level }}
            </p>
          </div>
          <div class="flex gap-3 text-xs text-faint">
            <Link :href="`/@${alter.handle}`" class="transition hover:text-ink">Profil</Link>
            <Link :href="`/alters/${alter.id}/edit`" class="transition hover:text-ink">Éditer</Link>
            <button class="transition hover:text-danger" @click="destroy(alter)">Supprimer</button>
          </div>
        </div>

        <p v-if="!alters.data.length" class="text-sm text-muted">Aucun alter pour l'instant.</p>
      </div>

      <section v-if="trashed.length" class="flex flex-col gap-3">
        <h2 class="za-eyebrow">Supprimés</h2>
        <p class="text-xs leading-relaxed text-faint">
          Invisibles partout : profil, recherche, feed, listes. Leur handle est reparti dans le
          stock — à la restauration, il faudra en choisir un autre s'il a été pris.
        </p>
        <div
          v-for="alter in trashed"
          :key="alter.id"
          class="flex flex-wrap items-center gap-3 rounded-[1.25rem] border border-dashed border-line px-4 py-3 text-sm text-muted"
        >
          <span class="flex-1 min-w-0 truncate">
            {{ alter.name }}
            <span class="text-faint">
              @{{ alter.previous_handle }}{{ alter.handle_available ? '' : ' — repris' }}
            </span>
          </span>
          <button class="za-btn-quiet" @click="restore(alter)">Restaurer</button>
        </div>
      </section>
    </div>
  </AppLayout>
</template>
