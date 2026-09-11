<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'

defineProps({ conversations: { type: Array, required: true } })

const form = useForm({ handle: '' })
</script>

<template>
  <Head title="Messages" />
  <AppLayout>
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-semibold">Messages</h1>
      <Link href="/settings/messaging" class="text-sm text-neutral-500 hover:text-neutral-200">Réglages</Link>
    </div>

    <form class="flex gap-2" @submit.prevent="form.post('/conversations')">
      <input
        v-model="form.handle"
        placeholder="Écrire à @handle"
        class="flex-1 rounded-md border border-neutral-800 bg-neutral-900 px-3 py-2 text-sm"
      />
      <button class="rounded-md bg-violet-600 px-4 py-2 text-sm font-medium hover:bg-violet-500">Ouvrir</button>
    </form>

    <ul class="space-y-2">
      <li v-for="conversation in conversations" :key="conversation.id">
        <Link
          :href="`/conversations/${conversation.id}`"
          class="flex items-center gap-3 rounded-lg border border-neutral-800 bg-neutral-900 p-3 text-sm hover:border-violet-700"
        >
          <span class="font-medium">{{ conversation.title }}</span>
          <span class="ml-auto text-xs text-neutral-600">
            {{ conversation.mode === 'shared' ? 'partagée' : 'perso' }}
          </span>
        </Link>
      </li>
    </ul>

    <p v-if="!conversations.length" class="text-sm text-neutral-500">Aucune conversation.</p>
  </AppLayout>
</template>
