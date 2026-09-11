<script setup>
import { computed } from 'vue'
import { alterGradient, initials } from '../lib/alter'

const props = defineProps({
  alter: { type: Object, required: true },
  size: { type: Number, default: 40 },
  ring: { type: Boolean, default: false },
})

// Couleur choisie dans les réglages, sinon dérivée de l'identifiant public.
const gradient = computed(() =>
  alterGradient(props.alter?.color !== null && props.alter?.color !== undefined
    ? String(props.alter.color)
    : props.alter?.id)
)

const style = computed(() => ({
  width: `${props.size}px`,
  height: `${props.size}px`,
  fontSize: `${Math.round(props.size * 0.38)}px`,
  ...(props.alter?.avatar_url ? {} : gradient.value),
  ...(props.ring ? { boxShadow: `0 0 0 2px var(--color-ground), 0 0 0 4px ${gradient.value.from}` } : {}),
}))
</script>

<template>
  <img
    v-if="alter?.avatar_url"
    :src="alter.avatar_url"
    :alt="alter.name"
    class="shrink-0 rounded-full object-cover"
    :style="style"
  />
  <span
    v-else
    class="flex shrink-0 items-center justify-center rounded-full font-bold"
    :style="style"
    aria-hidden="true"
  >
    {{ initials(alter?.name) }}
  </span>
</template>
