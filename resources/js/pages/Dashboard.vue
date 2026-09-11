<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../Layouts/AppLayout.vue'
import Avatar from '../Components/Avatar.vue'
import PostCard from '../Components/PostCard.vue'

defineProps({
  alters: { type: Object, required: true },
  posts: { type: Object, required: true },
  pendingRequests: { type: Number, default: 0 },
  pendingInvitations: { type: Number, default: 0 },
  notifications: { type: Array, default: () => [] },
})
</script>

<template>
  <Head title="Chez toi" />
  <AppLayout>
    <div class="flex flex-col gap-6">
      <header class="flex flex-col gap-2">
        <h1 class="font-display text-3xl">Chez toi</h1>
        <p class="text-sm leading-relaxed text-muted">
          Toute l'activité du système, rassemblée. Personne d'autre ne voit cette page — aucune
          route publique n'expose cette vue.
        </p>
      </header>

      <div class="flex flex-wrap gap-2">
        <Link
          v-for="alter in alters.data"
          :key="alter.id"
          :href="`/@${alter.handle}`"
          class="flex items-center gap-2 rounded-full border border-line px-2.5 py-1.5 text-sm transition hover:border-accent/60"
        >
          <Avatar :alter="alter" :size="24" />
          {{ alter.name }}
        </Link>
      </div>

      <div class="grid gap-3 sm:grid-cols-2">
        <Link href="/follows/requests" class="za-card flex items-baseline gap-3 p-5 transition hover:border-accent/40">
          <span class="font-display text-4xl text-accent">{{ pendingRequests }}</span>
          <span class="text-sm text-muted">demande{{ pendingRequests > 1 ? 's' : '' }} d'abonnement</span>
        </Link>
        <Link href="/posts/invitations" class="za-card flex items-baseline gap-3 p-5 transition hover:border-mint/40">
          <span class="font-display text-4xl text-mint">{{ pendingInvitations }}</span>
          <span class="text-sm text-muted">invitation{{ pendingInvitations > 1 ? 's' : '' }} de co-écriture</span>
        </Link>
      </div>

      <section v-if="notifications.length" class="flex flex-col gap-3">
        <h2 class="za-eyebrow">Notifications remontées</h2>
        <div class="za-card divide-y divide-line-soft">
          <p v-for="notification in notifications" :key="notification.id" class="px-4 py-3 text-sm text-muted">
            <span class="text-ink">{{ notification.payload?.actor ?? 'Quelqu\'un' }}</span>
            — {{ notification.type }}
            <span class="text-faint">pour {{ notification.for }}</span>
          </p>
        </div>
      </section>

      <section class="flex flex-col gap-4">
        <h2 class="za-eyebrow">Activité de tous les alters</h2>
        <PostCard v-for="post in posts.data" :key="post.id" :post="post" owned />
        <p v-if="!posts.data.length" class="text-sm text-muted">Aucun post pour l'instant.</p>
      </section>
    </div>
  </AppLayout>
</template>
