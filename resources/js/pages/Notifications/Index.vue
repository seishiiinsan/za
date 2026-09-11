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
    <h1 class="text-xl font-semibold">Notifications</h1>

    <ul class="space-y-2">
      <li
        v-for="notification in notifications"
        :key="notification.id"
        class="flex items-center gap-3 rounded-lg border p-3 text-sm"
        :class="notification.read ? 'border-neutral-900 text-neutral-500' : 'border-neutral-800 bg-neutral-900'"
      >
        <div class="min-w-0">
          <p>
            {{ notification.payload?.actor ?? 'Quelqu\'un' }} — {{ labels[notification.type] ?? notification.type }}
          </p>
          <p class="text-xs text-neutral-600">
            pour {{ notification.for }}
            <span v-if="notification.delegated" class="text-violet-400">· délégué</span>
          </p>
        </div>
        <button
          v-if="!notification.read"
          class="ml-auto text-xs text-neutral-500 hover:text-neutral-200"
          @click="markRead(notification)"
        >
          Marquer lu
        </button>
      </li>
    </ul>

    <p v-if="!notifications.length" class="text-sm text-neutral-500">Rien à signaler.</p>
  </AppLayout>
</template>
