<script setup>
import { ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'

/**
 * Messages fugaces, empilés en bas à droite.
 *
 * Empilés au sens propre : les plus anciens glissent derrière le dernier plutôt
 * que de s'aligner en colonne, pour ne pas manger l'écran.
 */
const page = usePage()
const toasts = ref([])
let sequence = 0

function push(message, tone) {
  if (!message) return

  const id = ++sequence
  toasts.value = [...toasts.value, { id, message, tone }]

  setTimeout(() => dismiss(id), tone === 'error' ? 6000 : 3200)
}

function dismiss(id) {
  toasts.value = toasts.value.filter((toast) => toast.id !== id)
}

watch(
  () => [page.props.flash?.status, page.props.flash?.error],
  ([status, error]) => {
    push(status, 'status')
    push(error, 'error')
  },
  { immediate: true }
)

// Le dernier est devant ; les précédents reculent de quelques pixels.
function layer(index, total) {
  const depth = total - 1 - index

  return {
    transform: `translateY(${depth * -7}px) scale(${1 - depth * 0.035})`,
    opacity: depth > 2 ? 0 : 1 - depth * 0.12,
    zIndex: 50 + index,
  }
}
</script>

<template>
  <div class="pointer-events-none fixed bottom-24 right-4 z-50 lg:bottom-6 lg:right-6">
    <div class="relative h-0 w-[min(20rem,calc(100vw-2rem))]">
      <TransitionGroup name="toast">
        <button
          v-for="(toast, index) in toasts"
          :key="toast.id"
          type="button"
          class="pointer-events-auto absolute bottom-0 right-0 w-full origin-bottom-right rounded-2xl border px-4 py-3 text-left text-sm shadow-[0_18px_40px_rgba(0,0,0,0.45)] backdrop-blur transition-all duration-200"
          :class="toast.tone === 'error'
            ? 'border-danger/40 bg-[#241318]/95 text-danger'
            : 'border-line bg-surface-2/95 text-ink'"
          :style="layer(index, toasts.length)"
          @click="dismiss(toast.id)"
        >
          {{ toast.message }}
        </button>
      </TransitionGroup>
    </div>
  </div>
</template>

<style scoped>
.toast-enter-from {
  opacity: 0;
  transform: translateY(12px) scale(0.96);
}

.toast-leave-to {
  opacity: 0;
  transform: translateY(6px) scale(0.98);
}

@media (prefers-reduced-motion: reduce) {
  .toast-enter-from,
  .toast-leave-to {
    transform: none;
  }
}
</style>
