<script setup>
import { nextTick, onMounted, ref, watch } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Avatar from '../../Components/Avatar.vue'

const props = defineProps({
  conversation: { type: Object, required: true },
  messages: { type: Array, required: true },
  myCorrespondentId: { type: Number, default: null },
})

const form = useForm({ content: '' })
const thread = ref(null)

function scrollToEnd() {
  nextTick(() => {
    if (thread.value) thread.value.scrollTop = thread.value.scrollHeight
  })
}

onMounted(scrollToEnd)
watch(() => props.messages.length, scrollToEnd)

function submit() {
  form.post(`/conversations/${props.conversation.id}/messages`, {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('content')
      scrollToEnd()
    },
  })
}

function mine(message) {
  return message.author_correspondent_id === props.myCorrespondentId
}

function when(value) {
  return new Date(value).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

// Un seul en-tête par salve : les messages qui se suivent restent groupés.
function startsGroup(index) {
  const previous = props.messages[index - 1]
  if (!previous) return true

  const current = props.messages[index]
  const gap = new Date(current.created_at) - new Date(previous.created_at)

  return previous.author_name !== current.author_name || gap > 5 * 60 * 1000
}
</script>

<template>
  <Head :title="conversation.title" />
  <AppLayout>
    <!-- Le fil scrolle, l'en-tête et le champ d'envoi ne bougent pas. -->
    <div class="flex h-[calc(100vh-9rem)] flex-col lg:h-[calc(100vh-6rem)]">
      <header class="flex items-center gap-3 border-b border-line-soft pb-3">
        <Link href="/conversations" class="za-btn-quiet" aria-label="Retour">←</Link>
        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-white/8 text-sm font-semibold">
          {{ conversation.title.charAt(0).toUpperCase() }}
        </span>
        <div class="min-w-0">
          <p class="truncate font-semibold">{{ conversation.title }}</p>
          <p class="text-xs text-faint">
            {{ conversation.mode === 'shared' ? 'compte partagé' : 'conversation perso' }}
          </p>
        </div>
      </header>

      <div ref="thread" class="flex min-h-0 flex-1 flex-col overflow-y-auto py-4">
        <div class="mt-auto flex flex-col gap-1">
        <div
          v-for="(message, index) in messages"
          :key="message.id"
          class="flex gap-2"
          :class="[mine(message) ? 'flex-row-reverse' : '', startsGroup(index) ? 'mt-3' : '']"
        >
          <Avatar
            v-if="startsGroup(index) && message.author_alter"
            :alter="message.author_alter"
            :size="30"
            class="mt-auto"
          />
          <span v-else class="w-[30px] shrink-0" />

          <div class="flex max-w-[78%] flex-col gap-1" :class="mine(message) ? 'items-end' : 'items-start'">
            <p v-if="startsGroup(index)" class="px-1 text-xs text-faint">
              <span class="font-semibold text-muted">{{ mine(message) ? 'Toi' : message.author_name }}</span>
              <span v-if="message.author_alter && !mine(message)"> · {{ message.author_alter.name }}</span>
              <span class="ml-1.5">{{ when(message.created_at) }}</span>
            </p>
            <p
              class="whitespace-pre-line rounded-2xl px-4 py-2.5 text-[0.97rem] leading-relaxed"
              :class="mine(message)
                ? 'rounded-br-md bg-accent text-accent-ink'
                : 'rounded-bl-md bg-surface-2 text-ink'"
            >
              {{ message.content }}
            </p>
          </div>
        </div>

        <p v-if="!messages.length" class="py-10 text-center text-sm text-muted">
          Aucun message. Écris le premier.
        </p>
        </div>
      </div>

      <form class="flex gap-2 border-t border-line-soft pt-3" @submit.prevent="submit">
        <input v-model="form.content" maxlength="4000" placeholder="Message" class="za-input" />
        <button type="submit" class="za-btn" :disabled="form.processing">Envoyer</button>
      </form>
    </div>
  </AppLayout>
</template>
