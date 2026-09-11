<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  title: { type: String, required: true },
  intro: { type: String, default: '' },
})
</script>

<template>
  <section class="mx-auto flex w-full max-w-sm flex-col gap-5 py-8">
    <div class="flex flex-col gap-2">
      <Link href="/" class="font-display text-4xl text-accent">座</Link>
      <h1 class="font-display text-3xl">{{ title }}</h1>
      <p v-if="intro" class="text-sm leading-relaxed text-muted">{{ intro }}</p>
    </div>

    <div class="za-card p-6">
      <slot />
    </div>

    <div v-if="$slots.footer" class="text-center text-sm text-faint">
      <slot name="footer" />
    </div>
  </section>
</template>
