<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'

const props = defineProps({
  term: { type: String, default: '' },
  results: { type: Object, required: true },
})

const q = ref(props.term)

function search() {
  router.get('/search', { q: q.value }, { preserveState: true, replace: true })
}
</script>

<template>
  <Head title="Recherche" />
  <AppLayout>
    <form class="flex gap-2" @submit.prevent="search">
      <input v-model="q" placeholder="Nom ou handle d'un alter" class="flex-1 rounded-md border border-neutral-800 bg-neutral-900 px-3 py-2 text-sm" />
      <button class="rounded-md bg-violet-600 px-4 py-2 text-sm font-medium hover:bg-violet-500">Chercher</button>
    </form>

    <p class="text-xs text-neutral-600">Za ne suggère jamais de comptes : une recommandation pourrait relier deux alters.</p>

    <ul class="space-y-2">
      <li v-for="alter in results.data" :key="alter.id" class="rounded-lg border border-neutral-800 bg-neutral-900 p-3">
        <Link :href="`/@${alter.handle}`" class="text-sm hover:text-violet-400">
          {{ alter.name }} <span class="text-neutral-600">@{{ alter.handle }}</span>
        </Link>
        <span v-if="alter.is_private" class="ml-2 text-xs text-neutral-600">privé</span>
      </li>
    </ul>

    <p v-if="term && !results.data.length" class="text-sm text-neutral-500">Aucun résultat.</p>
  </AppLayout>
</template>
