<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../Layouts/AppLayout.vue'
import PostCard from '../Components/PostCard.vue'

defineProps({
  alters: { type: Object, required: true },
  posts: { type: Object, required: true },
  pendingRequests: { type: Number, default: 0 },
})
</script>

<template>
  <Head title="Dashboard" />
  <AppLayout>
    <section class="rounded-lg border border-neutral-800 bg-neutral-900 p-4">
      <h1 class="text-lg font-semibold">Dashboard système</h1>
      <p class="text-sm text-neutral-500">Vue privée. Cette agrégation n'existe sur aucune surface publique.</p>

      <div class="mt-3 flex flex-wrap gap-2">
        <Link
          v-for="alter in alters.data"
          :key="alter.id"
          :href="`/@${alter.handle}`"
          class="rounded-full border border-neutral-800 px-3 py-1 text-xs hover:border-violet-600"
        >
          {{ alter.name }}
        </Link>
      </div>

      <p v-if="pendingRequests" class="mt-3 text-sm text-violet-300">
        {{ pendingRequests }} demande(s) d'abonnement en attente —
        <Link href="/follows/requests" class="underline">traiter</Link>
      </p>
    </section>

    <h2 class="text-sm uppercase tracking-wide text-neutral-500">Activité de tous les alters</h2>
    <div class="space-y-3">
      <PostCard v-for="post in posts.data" :key="post.id" :post="post" owned />
    </div>
    <p v-if="!posts.data.length" class="text-sm text-neutral-500">Aucun post pour l'instant.</p>
  </AppLayout>
</template>
