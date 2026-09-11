<script setup>
import { onBeforeUnmount, onMounted } from 'vue'

const props = defineProps({ title: { type: String, required: true } })
const emit = defineEmits(['close'])

function onKey(event) {
  if (event.key === 'Escape') emit('close')
}

onMounted(() => {
  document.addEventListener('keydown', onKey)
  document.body.style.overflow = 'hidden'
})

onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKey)
  document.body.style.overflow = ''
})
</script>

<template>
  <div class="fixed inset-0 z-50 flex items-end justify-center bg-black/65 p-0 backdrop-blur-sm sm:items-center sm:p-6" @click.self="emit('close')">
    <div class="flex max-h-[80vh] w-full max-w-md flex-col rounded-t-3xl border border-line bg-surface-2 sm:rounded-3xl">
      <header class="flex items-center gap-3 border-b border-line-soft px-5 py-3.5">
        <h2 class="font-semibold">{{ title }}</h2>
        <button type="button" class="ml-auto text-xl leading-none text-faint transition hover:text-ink" aria-label="Fermer" @click="emit('close')">
          ×
        </button>
      </header>
      <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
        <slot />
      </div>
    </div>
  </div>
</template>
