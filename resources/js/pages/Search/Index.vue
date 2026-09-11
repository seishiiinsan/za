<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Avatar from '../../Components/Avatar.vue'

const props = defineProps({
  term: { type: String, default: '' },
  results: { type: Object, required: true },
})

const query = ref(props.term)

function search() {
  router.get('/search', { q: query.value }, { preserveState: true, replace: true })
}
</script>

<template>
  <Head title="Recherche" />
  <AppLayout>
    <div class="flex flex-col gap-5">
      <form class="flex gap-2" @submit.prevent="search">
        <input v-model="query" placeholder="Nom ou @handle d'un alter" class="za-input" />
        <button class="za-btn">Chercher</button>
      </form>

      <p class="za-eyebrow">
        Za ne suggère jamais de comptes : une recommandation pourrait relier deux alters.
      </p>

      <Link
        v-for="alter in results.data"
        :key="alter.id"
        :href="`/@${alter.handle}`"
        class="za-card flex items-center gap-3 p-4 transition hover:border-accent/40"
      >
        <Avatar :alter="alter" :size="44" />
        <span class="min-w-0">
          <span class="block truncate font-semibold">{{ alter.name }}</span>
          <span class="block truncate text-sm text-faint">
            @{{ alter.handle }}<span v-if="alter.is_private"> · privé</span>
          </span>
        </span>
      </Link>

      <p v-if="term && !results.data.length" class="text-sm text-muted">Aucun résultat pour « {{ term }} ».</p>
    </div>
  </AppLayout>
</template>
