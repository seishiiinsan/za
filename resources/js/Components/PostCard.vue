<script setup>
import { ref } from 'vue'
import { Link, router, useForm, usePage } from '@inertiajs/vue3'

const props = defineProps({
  post: { type: Object, required: true },
  owned: { type: Boolean, default: false },
})

const page = usePage()
const showComments = ref(false)
const comment = useForm({ content: '' })

function toggleReaction() {
  const method = props.post.reacted ? 'delete' : 'post'
  router[method](`/posts/${props.post.id}/reaction`, {}, { preserveScroll: true })
}

function submitComment() {
  comment.post(`/posts/${props.post.id}/comments`, {
    preserveScroll: true,
    onSuccess: () => comment.reset('content'),
  })
}

function destroyComment(entry) {
  router.delete(`/comments/${entry.id}`, { preserveScroll: true })
}

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
    <footer class="mt-3 flex items-center gap-4 text-xs">
      <button
        class="hover:text-violet-400"
        :class="post.reacted ? 'text-violet-400' : 'text-neutral-500'"
        :disabled="!page.props.auth.activeAlter"
        @click="toggleReaction"
      >
        ♥ {{ post.reactions_count }}
      </button>
      <button class="text-neutral-500 hover:text-neutral-200" @click="showComments = !showComments">
        Commentaires ({{ post.comments_count }})
      </button>
      <button v-if="owned" class="ml-auto text-neutral-500 hover:text-red-400" @click="destroy">Supprimer</button>
    </footer>

    <section v-if="showComments" class="mt-3 space-y-3 border-t border-neutral-800 pt-3">
      <div v-for="entry in post.comments" :key="entry.id" class="text-sm">
        <p class="text-xs text-neutral-500">
          <Link v-if="entry.author.handle" :href="`/@${entry.author.handle}`" class="hover:text-violet-400">
            {{ entry.author.name }}
          </Link>
          <span v-else>{{ entry.author.name }}</span>
          <button class="ml-2 text-neutral-600 hover:text-red-400" @click="destroyComment(entry)">retirer</button>
        </p>
        <p class="whitespace-pre-line text-neutral-200">{{ entry.content }}</p>
      </div>

      <form v-if="page.props.auth.activeAlter" class="flex gap-2" @submit.prevent="submitComment">
        <input
          v-model="comment.content"
          placeholder="Commenter"
          maxlength="1000"
          class="flex-1 rounded-md border border-neutral-800 bg-neutral-950 px-3 py-1.5 text-xs outline-none focus:border-violet-500"
        />
        <button class="rounded-md border border-neutral-800 px-3 py-1.5 text-xs hover:border-violet-600">Envoyer</button>
      </form>
    </section>
  </article>
</template>
