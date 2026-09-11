<script setup>
import { useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const active = computed(() => page.props.auth.activeAlter)
const form = useForm({ content: '' })

function submit() {
  form.post('/posts', {
    preserveScroll: true,
    onSuccess: () => form.reset('content'),
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
