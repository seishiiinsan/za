<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import PostComposer from '../../Components/PostComposer.vue'
import PostCard from '../../Components/PostCard.vue'

defineProps({ posts: { type: Object, required: true } })
</script>

<template>
  <Head title="Feed" />
  <AppLayout>
    <PostComposer />

    <div class="space-y-3">
      <PostCard v-for="post in posts.data" :key="post.id" :post="post" />
    </div>

    <p v-if="!posts.data.length" class="text-sm text-neutral-500">
      Rien ici. Suis des alters depuis la <Link href="/search" class="text-violet-400">recherche</Link>.
    </p>

    <nav v-if="posts.meta?.last_page > 1" class="flex gap-2 text-sm">
      <Link
        v-for="link in posts.meta.links"
        :key="link.label"
        :href="link.url ?? '#'"
        class="rounded px-2 py-1"
        :class="link.active ? 'bg-neutral-800 text-neutral-100' : 'text-neutral-500 hover:text-neutral-200'"
        v-html="link.label"
      />
    </nav>
  </AppLayout>
</template>
