<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Avatar from '../../Components/Avatar.vue'

defineProps({
  followers: { type: Object, required: true },
  following: { type: Object, required: true },
})
</script>

<template>
  <Head title="Abonnements" />
  <AppLayout>
    <div class="flex flex-col gap-6">
      <header class="flex flex-wrap items-center gap-3">
        <h1 class="font-display text-3xl">Relations du front actif</h1>
        <Link href="/follows/requests" class="za-btn-quiet ml-auto">Demandes</Link>
      </header>

      <section class="grid gap-4 sm:grid-cols-2">
        <div class="za-card p-5">
          <h2 class="za-eyebrow mb-3">Abonnés</h2>
          <Link
            v-for="alter in followers.data"
            :key="alter.id"
            :href="`/@${alter.handle}`"
            class="flex items-center gap-2.5 py-1.5 text-sm transition hover:text-accent"
          >
            <Avatar :alter="alter" :size="28" />
            <span class="min-w-0 truncate">{{ alter.name }} <span class="text-faint">@{{ alter.handle }}</span></span>
          </Link>
          <p v-if="!followers.data.length" class="text-sm text-faint">Personne pour l'instant.</p>
        </div>

        <div class="za-card p-5">
          <h2 class="za-eyebrow mb-3">Abonnements</h2>
          <Link
            v-for="alter in following.data"
            :key="alter.id"
            :href="`/@${alter.handle}`"
            class="flex items-center gap-2.5 py-1.5 text-sm transition hover:text-accent"
          >
            <Avatar :alter="alter" :size="28" />
            <span class="min-w-0 truncate">{{ alter.name }} <span class="text-faint">@{{ alter.handle }}</span></span>
          </Link>
          <p v-if="!following.data.length" class="text-sm text-faint">Personne pour l'instant.</p>
        </div>
      </section>
    </div>
  </AppLayout>
</template>
