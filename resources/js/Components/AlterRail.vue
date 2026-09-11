<script setup>
import { computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import Avatar from './Avatar.vue'
import Icon from './Icon.vue'

const page = usePage()
const alters = computed(() => page.props.auth.alters)
const active = computed(() => page.props.auth.activeAlter)

function switchTo(alter) {
  if (alter.id === active.value?.id) return

  router.put('/front', { alter_id: alter.id }, { preserveScroll: true })
}
</script>

<template>
  <!-- Rail des alters : le front actif est toujours à l'écran, jamais dans un menu. -->
  <nav
    class="flex shrink-0 gap-3 overflow-x-auto border-line-soft px-4 py-3 lg:h-screen lg:w-[76px] lg:flex-col lg:items-center lg:overflow-visible lg:border-r lg:px-0 lg:py-5"
    aria-label="Alters du système"
  >
    <Link href="/" class="hidden font-display text-2xl text-accent lg:block" title="Za">座</Link>

    <div class="flex gap-3 lg:mt-2 lg:flex-col lg:items-center">
      <button
        v-for="alter in alters"
        :key="alter.id"
        type="button"
        class="group relative flex items-center justify-center transition"
        :title="`${alter.name} · @${alter.handle}`"
        @click="switchTo(alter)"
      >
        <span
          class="absolute -left-[15px] hidden h-8 w-[3px] rounded-r-full bg-accent transition-all lg:block"
          :class="alter.id === active?.id ? 'opacity-100' : 'h-2 opacity-0 group-hover:opacity-40'"
        />
        <Avatar :alter="alter" :size="alter.id === active?.id ? 46 : 40" :ring="alter.id === active?.id" />
      </button>

      <Link
        href="/alters/create"
        class="flex h-10 w-10 items-center justify-center rounded-full border border-dashed border-line text-faint transition hover:border-accent hover:text-accent"
        title="Nouvel alter"
      >
        <Icon name="invite" :size="18" />
      </Link>
    </div>
  </nav>
</template>
