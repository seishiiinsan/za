<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '../../Layouts/AppLayout.vue'
import PostCard from '../../Components/PostCard.vue'

const props = defineProps({
  alter: { type: Object, required: true },
  isSelf: Boolean,
  isFollowing: Boolean,
  hasPendingRequest: Boolean,
  posts: { type: Object, required: true },
  connections: { type: Object, default: null },
  counts: { type: Object, required: true },
})

const page = usePage()
const canFollow = computed(() => page.props.auth.activeAlter && !props.isSelf)

function follow() {
  router.post(`/alters/${props.alter.id}/follow`, {}, { preserveScroll: true })
}

function unfollow() {
  router.delete(`/alters/${props.alter.id}/follow`, { preserveScroll: true })
}
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
        <p class="mt-2 text-xs text-neutral-500">
          {{ counts.followers }} followers · {{ counts.following }} abonnements
        </p>
      </div>
      <div v-if="canFollow">
        <button v-if="isFollowing" class="rounded-md border border-neutral-700 px-3 py-1.5 text-sm hover:border-red-500" @click="unfollow">
          Se désabonner
        </button>
        <button v-else-if="hasPendingRequest" class="rounded-md border border-neutral-800 px-3 py-1.5 text-sm text-neutral-500" @click="unfollow">
          Demande en attente
        </button>
        <button v-else class="rounded-md bg-violet-600 px-3 py-1.5 text-sm font-medium hover:bg-violet-500" @click="follow">
          {{ alter.is_private ? 'Demander' : "S'abonner" }}
        </button>
      </div>
    </header>

    <div class="space-y-3">
      <PostCard v-for="post in posts.data" :key="post.id" :post="post" :owned="isSelf" />
    </div>
    <p v-if="!posts.data.length" class="text-sm text-neutral-500">Aucun post.</p>

    <section v-if="connections" class="grid gap-4 sm:grid-cols-2">
      <div>
        <h2 class="mb-2 text-sm uppercase tracking-wide text-neutral-500">Followers</h2>
        <Link v-for="a in connections.followers.data" :key="a.id" :href="`/@${a.handle}`" class="block text-sm text-neutral-300 hover:text-violet-400">
          {{ a.name }} <span class="text-neutral-600">@{{ a.handle }}</span>
        </Link>
      </div>
      <div>
        <h2 class="mb-2 text-sm uppercase tracking-wide text-neutral-500">Abonnements</h2>
        <Link v-for="a in connections.following.data" :key="a.id" :href="`/@${a.handle}`" class="block text-sm text-neutral-300 hover:text-violet-400">
          {{ a.name }} <span class="text-neutral-600">@{{ a.handle }}</span>
        </Link>
      </div>
    </section>
  </AppLayout>
</template>
