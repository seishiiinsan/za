<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Avatar from '../../Components/Avatar.vue'

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

function when(value) {
  return new Date(value).toLocaleString('fr-FR', { dateStyle: 'short', timeStyle: 'short' })
}
</script>

<template>
  <Head :title="conversation.title" />
  <AppLayout>
    <div class="flex flex-col gap-5">
      <header class="flex flex-wrap items-baseline gap-3">
        <Link href="/conversations" class="za-btn-quiet">← Messages</Link>
        <h1 class="font-display text-2xl">{{ conversation.title }}</h1>
        <span class="za-eyebrow">
          conversation {{ conversation.mode === 'shared' ? 'partagée' : 'perso' }}
        </span>
      </header>

      <div class="flex flex-col gap-3">
        <article v-for="message in messages" :key="message.id" class="za-card p-4">
          <p class="flex flex-wrap items-center gap-2 text-xs text-faint">
            <Avatar v-if="message.author_alter" :alter="message.author_alter" :size="22" />
            <span class="font-semibold text-muted">{{ message.author_name }}</span>
            <span v-if="message.author_alter">— {{ message.author_alter.name }}</span>
            <span class="ml-auto">{{ when(message.created_at) }}</span>
          </p>
          <p class="mt-2 text-[0.98rem] leading-relaxed whitespace-pre-line text-ink/90">{{ message.content }}</p>
        </article>

        <p v-if="!messages.length" class="text-sm text-muted">Aucun message pour l'instant.</p>
      </div>

      <form class="sticky bottom-20 flex gap-2 lg:bottom-4" @submit.prevent="submit">
        <input v-model="form.content" maxlength="4000" placeholder="Message" class="za-input" />
        <button class="za-btn" :disabled="form.processing">Envoyer</button>
      </form>
    </div>
  </AppLayout>
</template>
