<script setup>
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
  post: { type: Object, required: true },
  owned: { type: Boolean, default: false },
})

function destroy() {
  if (confirm('Supprimer ce post ?')) {
    router.delete(`/posts/${props.post.id}`, { preserveScroll: true })
  }
}
</script>

<template>
  <article class="rounded-lg border border-neutral-800 bg-neutral-900 p-4">
    <header class="mb-2 flex items-center gap-2 text-sm">
      <template v-for="author in post.authors" :key="author.id">
        <span v-if="author.deleted" class="flex items-center gap-2 text-neutral-500">
          <span class="h-5 w-5 rounded-full bg-neutral-800" />
          {{ author.name }}
        </span>
        <Link v-else :href="`/@${author.handle}`" class="font-medium text-neutral-100 hover:text-violet-400">
          {{ author.name }}
          <span class="text-neutral-500">@{{ author.handle }}</span>
        </Link>
      </template>
      <span class="ml-auto text-xs text-neutral-600">{{ new Date(post.created_at).toLocaleString() }}</span>
    </header>
    <p class="whitespace-pre-line text-sm text-neutral-200">{{ post.content }}</p>
    <footer v-if="owned" class="mt-3 flex justify-end">
      <button class="text-xs text-neutral-500 hover:text-red-400" @click="destroy">Supprimer</button>
    </footer>
  </article>
</template>
