<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'

defineProps({
  followers: { type: Object, required: true },
  following: { type: Object, required: true },
})
</script>

<template>
  <Head title="Abonnements" />
  <AppLayout>
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-semibold">Relations du front actif</h1>
      <Link href="/follows/requests" class="text-sm text-violet-400 hover:text-violet-300">Demandes</Link>
    </div>

    <section class="grid gap-4 sm:grid-cols-2">
      <div class="rounded-lg border border-neutral-800 bg-neutral-900 p-4">
        <h2 class="mb-2 text-sm uppercase tracking-wide text-neutral-500">Followers</h2>
        <Link v-for="a in followers.data" :key="a.id" :href="`/@${a.handle}`" class="block text-sm hover:text-violet-400">
          {{ a.name }} <span class="text-neutral-600">@{{ a.handle }}</span>
        </Link>
        <p v-if="!followers.data.length" class="text-sm text-neutral-600">Personne.</p>
      </div>
      <div class="rounded-lg border border-neutral-800 bg-neutral-900 p-4">
        <h2 class="mb-2 text-sm uppercase tracking-wide text-neutral-500">Abonnements</h2>
        <Link v-for="a in following.data" :key="a.id" :href="`/@${a.handle}`" class="block text-sm hover:text-violet-400">
          {{ a.name }} <span class="text-neutral-600">@{{ a.handle }}</span>
        </Link>
        <p v-if="!following.data.length" class="text-sm text-neutral-600">Personne.</p>
      </div>
    </section>
  </AppLayout>
</template>
