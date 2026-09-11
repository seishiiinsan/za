<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import Icon from './Icon.vue'

const page = usePage()
const open = ref(false)
const tab = ref('perso')
const root = ref(null)

const notifications = computed(() => page.props.notifications?.items ?? [])
const unread = computed(() => page.props.notifications?.unread ?? 0)

const labels = {
  follow: 'a suivi',
  follow_request: "demande à suivre",
  post_invitation: 'invite à écrire à deux',
  reaction: 'a réagi',
  comment: 'a commenté',
  message: 'a écrit',
}

const shown = computed(() =>
  notifications.value.filter((entry) => (tab.value === 'perso' ? !entry.delegated : entry.delegated))
)

function toggle() {
  open.value = !open.value

  // Ouvrir la cloche vaut lecture.
  if (open.value && unread.value > 0) {
    router.post('/notifications/read-all', {}, { preserveScroll: true, preserveState: true })
  }
}

function dismiss(entry) {
  router.delete(`/notifications/${entry.id}`, { preserveScroll: true, preserveState: true })
}

function onClickOutside(event) {
  if (open.value && root.value && !root.value.contains(event.target)) open.value = false
}

onMounted(() => document.addEventListener('click', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('click', onClickOutside))
</script>

<template>
  <div ref="root" class="relative">
    <button
      type="button"
      class="relative flex h-10 w-10 items-center justify-center rounded-full text-muted transition hover:bg-white/8 hover:text-ink"
      :class="open ? 'bg-white/8 text-ink' : ''"
      :aria-expanded="open"
      aria-label="Notifications"
      @click="toggle"
    >
      <Icon name="bell" :size="21" />
      <span
        v-if="unread"
        class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-accent px-1 text-[0.62rem] font-bold text-accent-ink"
      >
        {{ unread > 9 ? '9+' : unread }}
      </span>
    </button>

    <!-- Bulle pointant vers la cloche. -->
    <div
      v-if="open"
      class="absolute right-0 top-12 z-40 w-[min(21rem,calc(100vw-2rem))] lg:left-0 lg:right-auto rounded-2xl border border-line bg-surface-2/97 shadow-[0_22px_50px_rgba(0,0,0,0.5)] backdrop-blur"
    >
      <span class="absolute -top-1.5 right-4 h-3 w-3 rotate-45 border-l border-t border-line bg-surface-2 lg:left-4 lg:right-auto" />

      <div class="flex gap-1 border-b border-line-soft p-2">
        <button
          v-for="entry in [{ key: 'perso', label: 'Perso' }, { key: 'systeme', label: 'Système' }]"
          :key="entry.key"
          type="button"
          class="flex-1 rounded-xl px-3 py-1.5 text-sm transition"
          :class="tab === entry.key ? 'bg-white/8 font-semibold text-ink' : 'text-faint hover:text-muted'"
          @click="tab = entry.key"
        >
          {{ entry.label }}
        </button>
      </div>

      <div class="max-h-[22rem] overflow-y-auto p-2">
        <div
          v-for="entry in shown"
          :key="entry.id"
          class="group flex items-start gap-2 rounded-xl px-2.5 py-2 transition hover:bg-white/5"
        >
          <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full" :class="entry.read ? 'bg-transparent' : 'bg-accent'" />
          <p class="min-w-0 flex-1 text-sm leading-snug">
            <span class="font-semibold">{{ entry.payload?.actor ?? 'Quelqu\'un' }}</span>
            <span class="text-muted"> {{ labels[entry.type] ?? entry.type }}</span>
            <span class="block text-xs text-faint">pour {{ entry.for }}</span>
          </p>
          <button
            type="button"
            class="shrink-0 rounded-lg px-1.5 text-faint opacity-0 transition group-hover:opacity-100 hover:text-danger"
            aria-label="Retirer"
            @click="dismiss(entry)"
          >
            ×
          </button>
        </div>

        <p v-if="!shown.length" class="px-3 py-6 text-center text-sm text-faint">
          {{ tab === 'perso' ? 'Rien de nouveau.' : 'Aucune notification déléguée.' }}
        </p>
      </div>
    </div>
  </div>
</template>
