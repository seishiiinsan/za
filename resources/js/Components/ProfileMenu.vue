<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import Avatar from './Avatar.vue'
import Icon from './Icon.vue'

const page = usePage()
const open = ref(false)
const showAlters = ref(false)
const root = ref(null)

const active = computed(() => page.props.auth.activeAlter)
const alters = computed(() => page.props.auth.alters)

function switchTo(alter) {
  if (alter.id === active.value?.id) return

  router.put('/front', { alter_id: alter.id }, {
    preserveScroll: true,
    onSuccess: () => {
      open.value = false
      showAlters.value = false
    },
  })
}

function onClickOutside(event) {
  if (open.value && root.value && !root.value.contains(event.target)) {
    open.value = false
    showAlters.value = false
  }
}

onMounted(() => document.addEventListener('click', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('click', onClickOutside))
</script>

<template>
  <div ref="root" class="relative">
    <button
      type="button"
      class="flex w-full items-center gap-3 rounded-2xl px-3 py-2.5 text-left transition hover:bg-white/6"
      :class="open ? 'bg-white/6' : ''"
      :aria-expanded="open"
      @click="open = !open"
    >
      <Avatar v-if="active" :alter="active" :size="34" />
      <span class="min-w-0 flex-1 truncate text-[0.95rem] font-semibold">{{ active?.name }}</span>
      <span class="text-faint" aria-hidden="true">⋯</span>
    </button>

    <div
      v-if="open"
      class="absolute bottom-14 left-0 z-40 w-64 rounded-2xl border border-line bg-surface-2/97 p-1.5 shadow-[0_22px_50px_rgba(0,0,0,0.5)] backdrop-blur"
    >
      <!-- Survol ou clic : la liste des alters se déplie sur place. -->
      <div @mouseenter="showAlters = true" @mouseleave="showAlters = false">
        <button
          type="button"
          class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm transition hover:bg-white/6"
          @click="showAlters = !showAlters"
        >
          <Icon name="people" :size="18" />
          Changer d'alter
          <span class="ml-auto text-faint" aria-hidden="true">›</span>
        </button>

        <div v-if="showAlters" class="mb-1 flex flex-col gap-0.5 rounded-xl bg-black/25 p-1.5">
          <button
            v-for="alter in alters"
            :key="alter.id"
            type="button"
            class="flex items-center gap-2.5 rounded-lg px-2 py-1.5 text-left text-sm transition hover:bg-white/8"
            :class="alter.id === active?.id ? 'text-ink' : 'text-muted'"
            @click="switchTo(alter)"
          >
            <Avatar :alter="alter" :size="24" />
            <span class="min-w-0 flex-1 truncate">{{ alter.name }}</span>
            <span v-if="alter.id === active?.id" class="text-accent" aria-hidden="true">•</span>
          </button>
          <Link
            href="/alters/create"
            class="rounded-lg px-2 py-1.5 text-sm text-faint transition hover:bg-white/8 hover:text-muted"
          >
            + Nouvel alter
          </Link>
        </div>
      </div>

      <Link href="/settings" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm transition hover:bg-white/6">
        <Icon name="shield" :size="18" />
        Paramètres
      </Link>

      <button
        type="button"
        class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm text-muted transition hover:bg-white/6 hover:text-danger"
        @click="router.post('/logout')"
      >
        <Icon name="logout" :size="18" />
        Quitter
      </button>
    </div>
  </div>
</template>
