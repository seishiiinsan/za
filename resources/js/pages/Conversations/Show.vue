<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'

const props = defineProps({
  conversation: { type: Object, required: true },
  messages: { type: Array, required: true },
})

const form = useForm({ content: '' })

function submit() {
  form.post(`/conversations/${props.conversation.id}/messages`, {
    preserveScroll: true,
    onSuccess: () => form.reset('content'),
  })
}
</script>

<template>
  <Head :title="conversation.title" />
  <AppLayout>
    <header class="flex items-baseline gap-3">
      <h1 class="text-xl font-semibold">{{ conversation.title }}</h1>
      <span class="text-xs text-neutral-600">
        conversation {{ conversation.mode === 'shared' ? 'partagée' : 'perso' }}
      </span>
    </header>

    <div class="space-y-3">
      <article
        v-for="message in messages"
        :key="message.id"
        class="rounded-lg border border-neutral-800 bg-neutral-900 p-3"
      >
        <p class="mb-1 flex items-center gap-2 text-xs text-neutral-500">
          <img
            v-if="message.author_alter?.avatar_url"
            :src="message.author_alter.avatar_url"
            alt=""
            class="h-5 w-5 rounded-full object-cover"
          />
          <span>{{ message.author_name }}</span>
          <span v-if="message.author_alter" class="text-neutral-600">— {{ message.author_alter.name }}</span>
          <span class="ml-auto">{{ new Date(message.created_at).toLocaleString() }}</span>
        </p>
        <p class="whitespace-pre-line text-sm text-neutral-200">{{ message.content }}</p>
      </article>
    </div>

    <form class="flex gap-2" @submit.prevent="submit">
      <input
        v-model="form.content"
        placeholder="Message"
        maxlength="4000"
        class="flex-1 rounded-md border border-neutral-800 bg-neutral-900 px-3 py-2 text-sm"
      />
      <button class="rounded-md bg-violet-600 px-4 py-2 text-sm font-medium hover:bg-violet-500">Envoyer</button>
    </form>
  </AppLayout>
</template>
