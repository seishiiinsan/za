<script setup>
import { computed } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Avatar from '../../Components/Avatar.vue'
import PostCard from '../../Components/PostCard.vue'

const props = defineProps({
  alter: { type: Object, required: true },
  visible: Boolean,
  isSelf: Boolean,
  isFollowing: Boolean,
  hasPendingRequest: Boolean,
  canBlock: Boolean,
  posts: { type: Object, default: null },
  connections: { type: Object, default: null },
  counts: { type: Object, required: true },
})

const page = usePage()
const canAct = computed(() => page.props.auth.activeAlter && !props.isSelf)

function follow() {
  router.post(`/alters/${props.alter.id}/follow`, {}, { preserveScroll: true })
}

function unfollow() {
  router.delete(`/alters/${props.alter.id}/follow`, { preserveScroll: true })
}

function block(target) {
  const message = target === 'system'
    ? "Bloquer le compte entier ? Tous ses profils disparaissent, y compris ceux que tu ne connais pas. Za ne te dira pas lesquels."
    : `Bloquer ${props.alter.name} ? Les autres profils de la même personne continueront de te voir.`

  if (confirm(message)) {
    router.post(`/alters/${props.alter.id}/block`, { target }, { preserveScroll: true })
  }
}
</script>

<template>
  <Head :title="alter.name" />
  <AppLayout>
    <div class="flex flex-col gap-6">
      <header class="za-card overflow-hidden">
        <div class="flex flex-col items-center gap-4 px-6 py-8 text-center sm:flex-row sm:text-left">
          <Avatar :alter="alter" :size="92" />
          <div class="min-w-0 flex-1">
            <h1 class="font-display text-3xl">{{ alter.name }}</h1>
            <p class="text-sm text-faint">
              @{{ alter.handle }}<span v-if="alter.pronouns"> · {{ alter.pronouns }}</span>
              <span v-if="alter.is_private"> · privé</span>
            </p>
            <p v-if="visible && alter.bio" class="mt-3 text-[0.98rem] leading-relaxed whitespace-pre-line text-ink/85">
              {{ alter.bio }}
            </p>
            <p class="mt-3 text-sm text-muted">
              <span class="font-semibold text-ink">{{ counts.followers }}</span> abonnés ·
              <span class="font-semibold text-ink">{{ counts.following }}</span> abonnements
              <span v-if="!connections" class="text-faint"> · listes masquées</span>
            </p>
          </div>

          <div v-if="canAct" class="flex shrink-0 gap-2">
            <button v-if="isFollowing" class="za-btn-ghost" @click="unfollow">Se désabonner</button>
            <button v-else-if="hasPendingRequest" class="za-btn-ghost text-faint" @click="unfollow">
              Demande envoyée
            </button>
            <button v-else class="za-btn" @click="follow">
              {{ alter.is_private ? 'Demander' : "S'abonner" }}
            </button>
          </div>
        </div>

        <div v-if="canBlock" class="flex justify-end gap-4 border-t border-line-soft px-6 py-2.5 text-xs text-faint">
          <button class="transition hover:text-danger" @click="block('alter')">Bloquer cet alter</button>
          <button class="transition hover:text-danger" @click="block('system')">Bloquer le compte</button>
        </div>
      </header>

      <div v-if="!visible" class="za-card flex flex-col items-center gap-2 px-6 py-12 text-center">
        <p class="font-semibold">Ce profil est privé</p>
        <p class="max-w-[40ch] text-sm leading-relaxed text-muted">
          Ses posts apparaîtront une fois la demande acceptée.
        </p>
      </div>

      <template v-else>
        <div class="flex flex-col gap-5">
          <PostCard v-for="post in posts.data" :key="post.id" :post="post" :owned="isSelf" />
          <p v-if="!posts.data.length" class="text-sm text-muted">Aucun post pour l'instant.</p>
        </div>

        <section v-if="connections" class="grid gap-4 sm:grid-cols-2">
          <div class="za-card p-5">
            <h2 class="za-eyebrow mb-3">Abonnés</h2>
            <Link
              v-for="entry in connections.followers.data"
              :key="entry.id"
              :href="`/@${entry.handle}`"
              class="flex items-center gap-2.5 py-1.5 text-sm transition hover:text-accent"
            >
              <Avatar :alter="entry" :size="26" />
              {{ entry.name }}
            </Link>
          </div>
          <div class="za-card p-5">
            <h2 class="za-eyebrow mb-3">Abonnements</h2>
            <Link
              v-for="entry in connections.following.data"
              :key="entry.id"
              :href="`/@${entry.handle}`"
              class="flex items-center gap-2.5 py-1.5 text-sm transition hover:text-accent"
            >
              <Avatar :alter="entry" :size="26" />
              {{ entry.name }}
            </Link>
          </div>
        </section>
      </template>
    </div>
  </AppLayout>
</template>
