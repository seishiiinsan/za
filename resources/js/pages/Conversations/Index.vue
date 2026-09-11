<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'

defineProps({ conversations: { type: Array, required: true } })

const form = useForm({ handle: '' })

function initial(title) {
  return String(title ?? '?').trim().charAt(0).toUpperCase()
}
</script>

<template>
  <Head title="Messages" />
  <AppLayout>
    <div class="flex flex-col gap-5">
      <header class="flex flex-wrap items-center gap-3">
        <h1 class="font-display text-3xl">Messages</h1>
        <Link href="/settings/messaging" class="za-btn-quiet ml-auto">Réglages</Link>
      </header>

      <form class="flex gap-2" @submit.prevent="form.post('/conversations')">
        <input v-model="form.handle" placeholder="Écrire à @handle" class="za-input" />
        <button class="za-btn">Ouvrir</button>
      </form>

      <Link
        v-for="conversation in conversations"
        :key="conversation.id"
        :href="`/conversations/${conversation.id}`"
        class="za-card flex items-center gap-3 p-4 transition hover:border-accent/40"
      >
        <span
          class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white/8 text-sm font-semibold"
        >
          {{ initial(conversation.title) }}
        </span>
        <span class="min-w-0 flex-1">
          <span class="block truncate font-semibold">{{ conversation.title }}</span>
          <span class="block truncate text-xs text-faint">
            conversation {{ conversation.mode === 'shared' ? 'partagée' : 'perso' }}
          </span>
        </span>
      </Link>

      <p v-if="!conversations.length" class="text-sm text-muted">Aucune conversation.</p>
    </div>
  </AppLayout>
</template>
