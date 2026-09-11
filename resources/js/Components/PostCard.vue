<script setup>
import { ref } from 'vue'
import { Link, router, useForm, usePage } from '@inertiajs/vue3'
import Avatar from './Avatar.vue'

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

function when(value) {
  return new Date(value).toLocaleString('fr-FR', { dateStyle: 'medium', timeStyle: 'short' })
}
</script>

<template>
  <article class="za-card za-card-lifted p-5 sm:p-6">
    <header class="flex items-center gap-3">
      <!-- Co-écriture : les avatars se chevauchent, le lien reste par auteur. -->
      <div class="flex items-center">
        <template v-for="(author, index) in post.authors" :key="author.id">
          <Avatar
            :alter="author"
            :size="40"
            :class="index > 0 ? '-ml-3 ring-2 ring-ground' : ''"
          />
        </template>
      </div>

      <div class="min-w-0">
        <p class="flex flex-wrap items-center gap-x-1.5 text-[0.95rem] font-semibold">
          <template v-for="(author, index) in post.authors" :key="author.id">
            <span v-if="index > 0" class="font-normal text-faint">et</span>
            <Link v-if="author.handle" :href="`/@${author.handle}`" class="hover:text-accent">{{ author.name }}</Link>
            <span v-else class="text-muted">{{ author.name }}</span>
          </template>
        </p>
        <p class="text-xs text-faint">
          <span v-if="post.authors.length > 1">écrit à deux · </span>{{ when(post.created_at) }}
        </p>
      </div>

      <button v-if="owned" class="ml-auto text-xs text-faint transition hover:text-danger" @click="destroy">
        Supprimer
      </button>
    </header>

    <p class="mt-4 text-[1.02rem] leading-relaxed whitespace-pre-line text-ink/90">{{ post.content }}</p>

    <footer class="mt-4 flex flex-wrap items-center gap-2">
      <button
        class="za-btn-quiet"
        :class="post.reacted ? 'bg-accent/15 text-accent' : ''"
        :disabled="!page.props.auth.activeAlter"
        @click="toggleReaction"
      >
        ♥ {{ post.reactions_count }}
      </button>
      <button class="za-btn-quiet" @click="showComments = !showComments">
        {{ post.comments_count }} réponse{{ post.comments_count > 1 ? 's' : '' }}
      </button>
    </footer>

    <section v-if="showComments" class="mt-4 flex flex-col gap-4 border-t border-line-soft pt-4">
      <div v-for="entry in post.comments" :key="entry.id" class="flex gap-3">
        <Avatar :alter="entry.author" :size="30" />
        <div class="min-w-0 flex-1">
          <p class="text-xs text-faint">
            <Link v-if="entry.author.handle" :href="`/@${entry.author.handle}`" class="font-semibold text-muted hover:text-accent">
              {{ entry.author.name }}
            </Link>
            <span v-else class="font-semibold text-muted">{{ entry.author.name }}</span>
            <button class="ml-2 text-faint hover:text-danger" @click="destroyComment(entry)">retirer</button>
          </p>
          <p class="text-sm leading-relaxed whitespace-pre-line text-ink/85">{{ entry.content }}</p>
        </div>
      </div>

      <form v-if="page.props.auth.activeAlter" class="flex gap-2" @submit.prevent="submitComment">
        <input v-model="comment.content" maxlength="1000" placeholder="Répondre" class="za-input py-2.5 text-sm" />
        <button class="za-btn-ghost py-2.5">Envoyer</button>
      </form>
    </section>
  </article>
</template>
