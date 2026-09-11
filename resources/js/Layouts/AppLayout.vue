<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
const flash = computed(() => page.props.flash ?? {})
</script>

<template>
  <div class="min-h-screen">
    <header class="border-b border-neutral-900">
      <div class="mx-auto flex max-w-3xl flex-wrap items-center gap-4 px-4 py-3">
        <Link href="/" class="text-lg font-semibold tracking-tight">座<span class="sr-only">Za</span></Link>
      </div>
    </header>

    <main class="mx-auto max-w-3xl space-y-4 px-4 py-6">
      <p v-if="flash.status" class="rounded-md border border-violet-900 bg-violet-950/40 px-3 py-2 text-sm text-violet-200">
        {{ flash.status }}
      </p>
      <p v-if="flash.error" class="rounded-md border border-red-900 bg-red-950/40 px-3 py-2 text-sm text-red-200">
        {{ flash.error }}
      </p>
      <slot />
    </main>
  </div>
</template>
