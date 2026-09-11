<script setup>
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../Layouts/AppLayout.vue'
import Avatar from '../Components/Avatar.vue'
import Modal from '../Components/Modal.vue'
import PostCard from '../Components/PostCard.vue'

defineProps({
  alter: { type: Object, default: null },
  counts: { type: Object, required: true },
  posts: { type: Object, default: null },
  systemPosts: { type: Object, required: true },
  alters: { type: Object, required: true },
  followers: { type: Object, default: null },
  following: { type: Object, default: null },
  pendingRequests: { type: Number, default: 0 },
  pendingInvitations: { type: Number, default: 0 },
  notifications: { type: Array, default: () => [] },
})

const tab = ref('perso')
const modal = ref(null)
</script>

<template>
  <Head title="Chez toi" />
  <AppLayout>
    <div class="flex flex-col gap-6">
      <!-- L'en-tête suit l'alter au front : c'est son profil, vu de l'intérieur. -->
      <header class="flex items-center gap-5 sm:gap-7">
        <Avatar v-if="alter" :alter="alter" :size="96" />

        <div class="flex min-w-0 flex-1 flex-col gap-2">
          <div class="flex flex-wrap items-baseline gap-x-3">
            <h1 class="font-display text-3xl">{{ alter?.name }}</h1>
            <Link v-if="alter" :href="`/@${alter.handle}`" class="text-sm text-faint transition hover:text-accent">
              @{{ alter.handle }}
            </Link>
          </div>

          <div class="flex flex-wrap gap-x-6 gap-y-1 text-sm">
            <span><span class="font-semibold">{{ counts.posts }}</span> <span class="text-muted">posts</span></span>
            <button type="button" class="transition hover:text-accent" @click="modal = 'followers'">
              <span class="font-semibold">{{ counts.followers }}</span> <span class="text-muted">abonnés</span>
            </button>
            <button type="button" class="transition hover:text-accent" @click="modal = 'following'">
              <span class="font-semibold">{{ counts.following }}</span> <span class="text-muted">abonnements</span>
            </button>
          </div>

          <p v-if="alter?.bio" class="text-sm leading-relaxed text-muted">{{ alter.bio }}</p>
        </div>
      </header>

      <div v-if="pendingRequests || pendingInvitations" class="flex flex-wrap gap-2">
        <Link v-if="pendingRequests" href="/follows/requests" class="za-btn-quiet">
          {{ pendingRequests }} demande{{ pendingRequests > 1 ? 's' : '' }} d'abonnement
        </Link>
        <Link v-if="pendingInvitations" href="/posts/invitations" class="za-btn-quiet">
          {{ pendingInvitations }} invitation{{ pendingInvitations > 1 ? 's' : '' }} de co-écriture
        </Link>
      </div>

      <div class="flex gap-1 border-b border-line-soft">
        <button
          v-for="entry in [{ key: 'perso', label: 'Perso' }, { key: 'systeme', label: 'Système' }]"
          :key="entry.key"
          type="button"
          class="-mb-px border-b-2 px-4 py-2.5 text-sm transition"
          :class="tab === entry.key
            ? 'border-accent font-semibold text-ink'
            : 'border-transparent text-faint hover:text-muted'"
          @click="tab = entry.key"
        >
          {{ entry.label }}
        </button>
      </div>

      <template v-if="tab === 'perso'">
        <div class="flex flex-col gap-5">
          <PostCard v-for="post in posts?.data ?? []" :key="post.id" :post="post" owned />
          <p v-if="!posts?.data?.length" class="py-6 text-center text-sm text-muted">
            {{ alter?.name }} n'a rien publié pour l'instant.
          </p>
        </div>
      </template>

      <template v-else>
        <p class="za-eyebrow">
          Tout ce qu'écrivent les alters du système. Cette agrégation n'existe sur aucune route publique.
        </p>

        <section v-if="notifications.length" class="za-card divide-y divide-line-soft">
          <p v-for="notification in notifications" :key="notification.id" class="px-4 py-2.5 text-sm text-muted">
            <span class="text-ink">{{ notification.payload?.actor ?? 'Quelqu\'un' }}</span>
            — {{ notification.type }} <span class="text-faint">pour {{ notification.for }}</span>
          </p>
        </section>

        <div class="flex flex-col gap-5">
          <PostCard v-for="post in systemPosts.data" :key="post.id" :post="post" owned />
          <p v-if="!systemPosts.data.length" class="py-6 text-center text-sm text-muted">Aucun post pour l'instant.</p>
        </div>
      </template>
    </div>

    <Modal v-if="modal === 'followers'" title="Abonnés" @close="modal = null">
      <Link
        v-for="entry in followers?.data ?? []"
        :key="entry.id"
        :href="`/@${entry.handle}`"
        class="flex items-center gap-3 rounded-xl px-2 py-2 transition hover:bg-white/5"
      >
        <Avatar :alter="entry" :size="38" />
        <span class="min-w-0">
          <span class="block truncate text-sm font-semibold">{{ entry.name }}</span>
          <span class="block truncate text-xs text-faint">@{{ entry.handle }}</span>
        </span>
      </Link>
      <p v-if="!followers?.data?.length" class="py-6 text-center text-sm text-faint">Personne pour l'instant.</p>
    </Modal>

    <Modal v-if="modal === 'following'" title="Abonnements" @close="modal = null">
      <Link
        v-for="entry in following?.data ?? []"
        :key="entry.id"
        :href="`/@${entry.handle}`"
        class="flex items-center gap-3 rounded-xl px-2 py-2 transition hover:bg-white/5"
      >
        <Avatar :alter="entry" :size="38" />
        <span class="min-w-0">
          <span class="block truncate text-sm font-semibold">{{ entry.name }}</span>
          <span class="block truncate text-xs text-faint">@{{ entry.handle }}</span>
        </span>
      </Link>
      <p v-if="!following?.data?.length" class="py-6 text-center text-sm text-faint">Personne pour l'instant.</p>
    </Modal>
  </AppLayout>
</template>
