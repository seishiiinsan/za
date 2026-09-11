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
    <div class="flex flex-col gap-5">
      <PostComposer />

      <PostCard v-for="post in posts.data" :key="post.id" :post="post" />

      <div v-if="!posts.data.length" class="za-card flex flex-col items-center gap-3 px-6 py-12 text-center">
        <span class="font-display text-4xl text-accent">座</span>
        <p class="text-muted">Ton feed est vide pour l'instant.</p>
        <Link href="/search" class="za-btn">Trouver des alters à suivre</Link>
      </div>

      <nav v-if="posts.meta?.last_page > 1" class="flex flex-wrap justify-center gap-1.5 pb-4">
        <Link
          v-for="link in posts.meta.links"
          :key="link.label"
          :href="link.url ?? '#'"
          class="rounded-xl px-3 py-1.5 text-sm"
          :class="link.active ? 'bg-white/10 text-ink' : 'text-faint hover:text-muted'"
          v-html="link.label"
        />
      </nav>
    </div>
  </AppLayout>
</template>
