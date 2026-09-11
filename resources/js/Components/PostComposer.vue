<script setup>
import { useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const page = usePage()
const active = computed(() => page.props.auth.activeAlter)
const form = useForm({ content: '', co_authors: [] })
const coAuthorHandle = ref('')
const coAuthors = ref([])

async function addCoAuthor() {
  const handle = coAuthorHandle.value.trim().replace(/^@/, '')
  if (!handle || coAuthors.value.some((a) => a.handle === handle)) return

  const response = await fetch(`/api/alters/${handle}`, { headers: { Accept: 'application/json' } })
  if (!response.ok) {
    lookupError.value = "Aucun alter public ne porte ce handle."
    return
  }

  const alter = await response.json()
  coAuthors.value.push(alter)
  form.co_authors.push(alter.id)
  coAuthorHandle.value = ''
  lookupError.value = ''
}

function removeCoAuthor(alter) {
  coAuthors.value = coAuthors.value.filter((a) => a.id !== alter.id)
  form.co_authors = form.co_authors.filter((id) => id !== alter.id)
}

const lookupError = ref('')

function submit() {
  form.post('/posts', {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('content', 'co_authors')
      coAuthors.value = []
    },
  })
}
</script>

<template>
  <form class="rounded-lg border border-neutral-800 bg-neutral-900 p-4" @submit.prevent="submit">
    <p class="mb-2 text-xs text-neutral-500">
      Publié par <span class="text-neutral-300">{{ active?.name }}</span>
    </p>
    <textarea
      v-model="form.content"
      rows="3"
      maxlength="2000"
      placeholder="Quoi de neuf ?"
      class="w-full resize-none rounded-md border border-neutral-800 bg-neutral-950 p-3 text-sm outline-none focus:border-violet-500"
    />
    <p v-if="form.errors.content" class="mt-1 text-xs text-red-400">{{ form.errors.content }}</p>
    <div class="mt-3 space-y-2">
      <div class="flex flex-wrap items-center gap-2">
        <span
          v-for="alter in coAuthors"
          :key="alter.id"
          class="flex items-center gap-1 rounded-full border border-neutral-800 px-2 py-0.5 text-xs text-neutral-300"
        >
          {{ alter.name }}
          <button type="button" class="text-neutral-500 hover:text-red-400" @click="removeCoAuthor(alter)">×</button>
        </span>
      </div>
      <div class="flex gap-2">
        <input
          v-model="coAuthorHandle"
          placeholder="Inviter un co-auteur (@handle)"
          class="flex-1 rounded-md border border-neutral-800 bg-neutral-950 px-3 py-1.5 text-xs outline-none focus:border-violet-500"
          @keydown.enter.prevent="addCoAuthor"
        />
        <button type="button" class="rounded-md border border-neutral-800 px-3 py-1.5 text-xs hover:border-violet-600" @click="addCoAuthor">
          Inviter
        </button>
      </div>
      <p v-if="lookupError" class="text-xs text-red-400">{{ lookupError }}</p>
      <p v-if="coAuthors.length" class="text-xs text-neutral-600">
        Le post restera en attente tant que chaque invité·e n'aura pas accepté.
      </p>
    </div>

    <div class="mt-3 flex justify-end">
      <button
        type="submit"
        :disabled="form.processing"
        class="rounded-md bg-violet-600 px-4 py-1.5 text-sm font-medium hover:bg-violet-500 disabled:opacity-50"
      >
        Publier
      </button>
    </div>
  </form>
</template>
