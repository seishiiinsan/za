<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'

defineProps({ alter: { type: Object, required: true } })
</script>

<template>
  <Head :title="alter.name" />
  <AppLayout>
    <header class="flex items-start gap-4 rounded-lg border border-neutral-800 bg-neutral-900 p-4">
      <img v-if="alter.avatar_url" :src="alter.avatar_url" alt="" class="h-16 w-16 rounded-full object-cover" />
      <div v-else class="h-16 w-16 rounded-full bg-neutral-800" />
      <div class="min-w-0 flex-1">
        <h1 class="text-lg font-semibold">{{ alter.name }}</h1>
        <p class="text-sm text-neutral-500">@{{ alter.handle }} <span v-if="alter.pronouns">· {{ alter.pronouns }}</span></p>
        <p v-if="alter.bio" class="mt-2 whitespace-pre-line text-sm text-neutral-300">{{ alter.bio }}</p>
      </div>
    </header>
  </AppLayout>
</template>
