<script setup>
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'

defineProps({ invitations: { type: Object, required: true } })

function accept(post) {
  router.post(`/posts/${post.id}/invitation`, {}, { preserveScroll: true })
}

function decline(post) {
  router.delete(`/posts/${post.id}/invitation`, { preserveScroll: true })
}
</script>

<template>
  <Head title="Invitations de co-écriture" />
  <AppLayout>
    <h1 class="text-xl font-semibold">Invitations de co-écriture</h1>
    <p class="text-sm text-neutral-500">
      Un post reste privé tant que tous ses auteurs n'ont pas accepté.
    </p>

    <article
      v-for="post in invitations.data"
      :key="post.id"
      class="space-y-3 rounded-lg border border-neutral-800 bg-neutral-900 p-4"
    >
      <p class="text-xs text-neutral-500">
        Avec
        <span v-for="author in post.authors" :key="author.id" class="text-neutral-300">{{ author.name }} </span>
      </p>
      <p class="whitespace-pre-line text-sm text-neutral-200">{{ post.content }}</p>
      <div class="flex justify-end gap-2 text-xs">
        <button class="rounded-md bg-violet-600 px-3 py-1 hover:bg-violet-500" @click="accept(post)">Accepter</button>
        <button class="rounded-md border border-neutral-700 px-3 py-1 hover:border-red-500" @click="decline(post)">
          Refuser
        </button>
      </div>
    </article>

    <p v-if="!invitations.data.length" class="text-sm text-neutral-500">Aucune invitation.</p>
  </AppLayout>
</template>
