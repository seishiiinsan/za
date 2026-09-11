<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Avatar from '../../Components/Avatar.vue'

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
    <div class="flex flex-col gap-5">
      <h1 class="font-display text-3xl">Demandes d'abonnement</h1>

      <div v-for="alter in requests.data" :key="alter.id" class="za-card flex flex-wrap items-center gap-3 p-4">
        <Avatar :alter="alter" :size="42" />
        <Link :href="`/@${alter.handle}`" class="min-w-0 flex-1 truncate text-sm transition hover:text-accent">
          <span class="font-semibold">{{ alter.name }}</span>
          <span class="text-faint"> @{{ alter.handle }}</span>
        </Link>
        <div class="flex gap-2">
          <button class="za-btn py-2" @click="approve(alter)">Accepter</button>
          <button class="za-btn-ghost py-2" @click="reject(alter)">Refuser</button>
        </div>
      </div>

      <p v-if="!requests.data.length" class="text-sm text-muted">Aucune demande en attente.</p>
    </div>
  </AppLayout>
</template>
