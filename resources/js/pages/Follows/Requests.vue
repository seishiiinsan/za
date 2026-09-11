<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'

defineProps({ requests: { type: Object, required: true } })

function approve(alter) {
  router.post(`/follows/requests/${alter.id}`, {}, { preserveScroll: true })
}

function reject(alter) {
  router.delete(`/follows/requests/${alter.id}`, { preserveScroll: true })
}
</script>

<template>
  <Head title="Demandes d'abonnement" />
  <AppLayout>
    <h1 class="text-xl font-semibold">Demandes d'abonnement</h1>

    <ul class="space-y-2">
      <li v-for="alter in requests.data" :key="alter.id" class="flex items-center gap-3 rounded-lg border border-neutral-800 bg-neutral-900 p-3">
        <Link :href="`/@${alter.handle}`" class="text-sm hover:text-violet-400">
          {{ alter.name }} <span class="text-neutral-600">@{{ alter.handle }}</span>
        </Link>
        <div class="ml-auto flex gap-2 text-xs">
          <button class="rounded-md bg-violet-600 px-3 py-1 hover:bg-violet-500" @click="approve(alter)">Accepter</button>
          <button class="rounded-md border border-neutral-700 px-3 py-1 hover:border-red-500" @click="reject(alter)">Refuser</button>
        </div>
      </li>
    </ul>

    <p v-if="!requests.data.length" class="text-sm text-neutral-500">Aucune demande.</p>
  </AppLayout>
</template>
