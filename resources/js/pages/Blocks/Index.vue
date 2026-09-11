<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Avatar from '../../Components/Avatar.vue'

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
    <div class="flex flex-col gap-7">
      <h1 class="font-display text-3xl">Blocages du front actif</h1>

      <section class="flex flex-col gap-3">
        <h2 class="za-eyebrow">Alters bloqués</h2>
        <p class="text-xs leading-relaxed text-faint">
          Ne masque que cet alter. Les autres profils de la même personne continuent de te voir.
        </p>
        <div v-for="alter in alters.data" :key="alter.id" class="za-card flex items-center gap-3 p-4">
          <Avatar :alter="alter" :size="38" />
          <Link :href="`/@${alter.handle}`" class="min-w-0 flex-1 truncate text-sm transition hover:text-accent">
            {{ alter.name }} <span class="text-faint">@{{ alter.handle }}</span>
          </Link>
        </div>
        <p v-if="!alters.data.length" class="text-sm text-faint">Aucun.</p>
      </section>

      <section class="flex flex-col gap-3">
        <h2 class="za-eyebrow">Comptes bloqués</h2>
        <p class="text-xs leading-relaxed text-faint">
          Masque tous les profils du compte, y compris ceux que tu ne connais pas. Za ne te dit pas
          lesquels : ce serait te révéler la personne derrière.
        </p>
        <div
          v-for="block in systemBlocks"
          :key="block.id"
          class="za-card flex flex-wrap items-center gap-3 p-4 text-sm text-muted"
        >
          <span class="flex-1">Compte bloqué le {{ new Date(block.blocked_at).toLocaleDateString('fr-FR') }}</span>
          <button class="za-btn-quiet" @click="unblock(block.id)">Débloquer</button>
        </div>
        <p v-if="!systemBlocks.length" class="text-sm text-faint">Aucun.</p>
      </section>
    </div>
  </AppLayout>
</template>
