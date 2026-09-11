<script setup>
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Avatar from '../../Components/Avatar.vue'

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
    <div class="flex flex-col gap-5">
      <header class="flex flex-col gap-2">
        <h1 class="font-display text-3xl">Invitations de co-écriture</h1>
        <p class="text-sm leading-relaxed text-muted">
          Un post à plusieurs reste privé tant que chaque auteur n'a pas accepté.
        </p>
      </header>

      <article v-for="post in invitations.data" :key="post.id" class="za-card flex flex-col gap-4 p-5">
        <div class="flex items-center gap-2">
          <div class="flex items-center">
            <Avatar
              v-for="(author, index) in post.authors"
              :key="author.id"
              :alter="author"
              :size="32"
              :class="index > 0 ? '-ml-2.5 ring-2 ring-ground' : ''"
            />
          </div>
          <p class="text-sm text-muted">
            avec
            <span v-for="author in post.authors" :key="author.id" class="text-ink">{{ author.name }} </span>
          </p>
        </div>

        <p class="text-[1.02rem] leading-relaxed whitespace-pre-line text-ink/90">{{ post.content }}</p>

        <div class="flex justify-end gap-2">
          <button class="za-btn py-2" @click="accept(post)">Accepter</button>
          <button class="za-btn-ghost py-2" @click="decline(post)">Refuser</button>
        </div>
      </article>

      <p v-if="!invitations.data.length" class="text-sm text-muted">Aucune invitation.</p>
    </div>
  </AppLayout>
</template>
