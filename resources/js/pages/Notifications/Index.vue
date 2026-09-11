<script setup>
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'

defineProps({ notifications: { type: Array, required: true } })

const labels = {
  follow: 'nouvel abonnement',
  follow_request: "demande d'abonnement",
  post_invitation: 'invitation de co-écriture',
  reaction: 'réaction',
  comment: 'commentaire',
  message: 'message',
}

function markRead(notification) {
  router.post(`/notifications/${notification.id}/read`, {}, { preserveScroll: true })
}
</script>

<template>
  <Head title="Notifications" />
  <AppLayout>
    <div class="flex flex-col gap-5">
      <h1 class="font-display text-3xl">Notifications</h1>

      <div
        v-for="notification in notifications"
        :key="notification.id"
        class="flex flex-wrap items-center gap-3 rounded-[1.25rem] border px-4 py-3 text-sm"
        :class="notification.read
          ? 'border-line-soft text-faint'
          : 'border-line bg-surface/70 text-ink'"
      >
        <span class="min-w-0 flex-1">
          <span class="block truncate">
            {{ notification.payload?.actor ?? 'Quelqu\'un' }} — {{ labels[notification.type] ?? notification.type }}
          </span>
          <span class="block truncate text-xs text-faint">
            pour {{ notification.for }}
            <span v-if="notification.delegated" class="text-accent">· délégué</span>
            <span v-if="notification.shared">· boîte commune</span>
          </span>
        </span>
        <button v-if="!notification.read" class="za-btn-quiet" @click="markRead(notification)">
          {{ notification.shared ? 'Marquer lu pour tous' : 'Marquer lu' }}
        </button>
      </div>

      <p v-if="!notifications.length" class="text-sm text-muted">Rien à signaler.</p>
    </div>
  </AppLayout>
</template>
