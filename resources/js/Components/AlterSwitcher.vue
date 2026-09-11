<script setup>
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const page = usePage()
const alters = computed(() => page.props.auth.alters)
const active = computed(() => page.props.auth.activeAlter)

function switchTo(event) {
  const id = event.target.value
  if (!id || id === active.value?.id) return
  router.put('/front', { alter_id: id }, { preserveScroll: true })
}
</script>

<template>
  <div v-if="alters.length" class="flex items-center gap-2">
    <span class="text-xs uppercase tracking-wide text-neutral-500">Front</span>
    <select
      class="rounded-md border border-neutral-800 bg-neutral-900 px-2 py-1 text-sm"
      :value="active?.id"
      @change="switchTo"
    >
      <option v-for="alter in alters" :key="alter.id" :value="alter.id">
        {{ alter.name }}
      </option>
    </select>
  </div>
</template>
