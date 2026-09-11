<script setup>
import { computed, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Avatar from '../../Components/Avatar.vue'
import Modal from '../../Components/Modal.vue'

const props = defineProps({
  conversations: { type: Array, required: true },
  contacts: { type: Object, required: true },
})

const picker = ref(false)
const query = ref('')

// Le sélecteur s'ouvre sur les gens que l'alter connaît déjà, puis filtre dedans.
const matches = computed(() => {
  const needle = query.value.trim().toLowerCase().replace(/^@/, '')

  return props.contacts.data.filter(
    (alter) => !needle || alter.name.toLowerCase().includes(needle) || alter.handle.includes(needle)
  )
})

function open(alter) {
  router.post('/conversations', { handle: alter.handle }, { onSuccess: () => (picker.value = false) })
}

function when(value) {
  const date = new Date(value)
  const today = new Date().toDateString() === date.toDateString()

  return today
    ? date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
    : date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' })
}

function initial(title) {
  return String(title ?? '?').trim().charAt(0).toUpperCase()
}
</script>

<template>
  <Head title="Messages" />
  <AppLayout>
    <div class="flex flex-col gap-4">
      <header class="flex items-center gap-3">
        <h1 class="font-display text-3xl">Messages</h1>
        <button type="button" class="za-btn ml-auto py-2" @click="picker = true">Nouveau</button>
      </header>

      <div class="za-card divide-y divide-line-soft overflow-hidden">
        <Link
          v-for="conversation in conversations"
          :key="conversation.id"
          :href="`/conversations/${conversation.id}`"
          class="flex items-center gap-3 px-4 py-3 transition hover:bg-white/4"
        >
          <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white/8 font-semibold">
            {{ initial(conversation.title) }}
          </span>

          <span class="min-w-0 flex-1">
            <span class="flex items-baseline gap-2">
              <span class="min-w-0 truncate" :class="conversation.unread ? 'font-semibold' : 'font-medium'">
                {{ conversation.title }}
              </span>
              <span v-if="conversation.mode === 'shared'" class="shrink-0 text-[0.65rem] text-faint">compte</span>
              <span v-if="conversation.last_message" class="ml-auto shrink-0 text-xs text-faint">
                {{ when(conversation.last_message.at) }}
              </span>
            </span>
            <span
              class="mt-0.5 block truncate text-sm"
              :class="conversation.unread ? 'text-ink' : 'text-faint'"
            >
              <template v-if="conversation.last_message">
                <span class="text-muted">{{ conversation.last_message.author }} :</span>
                {{ conversation.last_message.excerpt }}
              </template>
              <template v-else>Aucun message</template>
            </span>
          </span>

          <span v-if="conversation.unread" class="h-2.5 w-2.5 shrink-0 rounded-full bg-accent" aria-label="Non lu" />
        </Link>

        <p v-if="!conversations.length" class="px-4 py-10 text-center text-sm text-muted">
          Aucune conversation. Commence par « Nouveau ».
        </p>
      </div>
    </div>

    <Modal v-if="picker" title="Écrire à" @close="picker = false">
      <input v-model="query" placeholder="Filtrer par nom ou @handle" class="za-input mb-3 py-2.5 text-sm" />

      <button
        v-for="alter in matches"
        :key="alter.id"
        type="button"
        class="flex w-full items-center gap-3 rounded-xl px-2 py-2 text-left transition hover:bg-white/5"
        @click="open(alter)"
      >
        <Avatar :alter="alter" :size="38" />
        <span class="min-w-0">
          <span class="block truncate text-sm font-semibold">{{ alter.name }}</span>
          <span class="block truncate text-xs text-faint">@{{ alter.handle }}</span>
        </span>
      </button>

      <p v-if="!matches.length" class="py-6 text-center text-sm text-faint">
        {{ contacts.data.length ? 'Personne ne correspond.' : "Cet alter n'a encore ni abonnés ni abonnements." }}
      </p>
    </Modal>
  </AppLayout>
</template>
