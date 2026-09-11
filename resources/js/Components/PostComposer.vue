<script setup>
import { computed, ref } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import Avatar from './Avatar.vue'

const page = usePage()
const active = computed(() => page.props.auth.activeAlter)

const open = ref(false)
const form = useForm({ content: '', co_authors: [] })
const coAuthors = ref([])
const handle = ref('')
const lookupError = ref('')

async function addCoAuthor() {
  const wanted = handle.value.trim().replace(/^@/, '')
  if (!wanted || coAuthors.value.some((alter) => alter.handle === wanted)) return

  const response = await fetch(`/api/alters/${wanted}`, { headers: { Accept: 'application/json' } })

  if (!response.ok) {
    lookupError.value = "Aucun alter public ne porte ce handle."
    return
  }

  const alter = await response.json()
  coAuthors.value.push(alter)
  form.co_authors.push(alter.id)
  handle.value = ''
  lookupError.value = ''
}

function removeCoAuthor(alter) {
  coAuthors.value = coAuthors.value.filter((entry) => entry.id !== alter.id)
  form.co_authors = form.co_authors.filter((id) => id !== alter.id)
}

function submit() {
  form.post('/posts', {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('content', 'co_authors')
      coAuthors.value = []
      open.value = false
    },
  })
}
</script>

<template>
  <!-- Replié, le composer est une seule ligne : le feed reste l'essentiel. -->
  <div class="za-card p-4">
    <button
      v-if="!open"
      type="button"
      class="flex w-full items-center gap-3 text-left"
      @click="open = true"
    >
      <Avatar v-if="active" :alter="active" :size="38" />
      <span class="flex-1 text-[0.95rem] text-faint">Raconte ta journée, {{ active?.name }}…</span>
      <span class="za-btn">Publier</span>
    </button>

    <form v-else class="flex flex-col gap-3" @submit.prevent="submit">
      <div class="flex items-center gap-3">
        <Avatar v-if="active" :alter="active" :size="38" />
        <span class="text-sm text-muted">
          Tu écris en tant que <span class="font-semibold text-ink">{{ active?.name }}</span>
        </span>
      </div>

      <textarea
        v-model="form.content"
        rows="4"
        maxlength="2000"
        autofocus
        placeholder="Quoi de neuf ?"
        class="za-input resize-none text-[1rem] leading-relaxed"
      />
      <p v-if="form.errors.content" class="za-error">{{ form.errors.content }}</p>

      <div v-if="coAuthors.length" class="flex flex-wrap gap-2">
        <span
          v-for="alter in coAuthors"
          :key="alter.id"
          class="flex items-center gap-2 rounded-full border border-line px-2.5 py-1 text-xs text-muted"
        >
          <Avatar :alter="alter" :size="18" />
          {{ alter.name }}
          <button type="button" class="text-faint hover:text-danger" @click="removeCoAuthor(alter)">×</button>
        </span>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <input
          v-model="handle"
          placeholder="Inviter un co-auteur (@handle)"
          class="za-input flex-1 py-2 text-sm"
          @keydown.enter.prevent="addCoAuthor"
        />
        <button type="button" class="za-btn-ghost py-2" @click="addCoAuthor">Inviter</button>
      </div>
      <p v-if="lookupError" class="za-error">{{ lookupError }}</p>
      <p v-if="coAuthors.length" class="za-eyebrow">
        Le post restera privé tant que chaque invité·e n'aura pas accepté.
      </p>

      <div class="flex justify-end gap-2">
        <button type="button" class="za-btn-quiet" @click="open = false">Annuler</button>
        <button type="submit" class="za-btn" :disabled="form.processing">Publier</button>
      </div>
    </form>
  </div>
</template>
