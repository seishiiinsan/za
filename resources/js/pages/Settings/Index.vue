<script setup>
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import Avatar from '../../Components/Avatar.vue'
import Icon from '../../Components/Icon.vue'

const props = defineProps({
  alter: { type: Object, default: null },
  alters: { type: Object, required: true },
  email: { type: String, required: true },
  verified: Boolean,
})

const tab = ref('alter')

const alterEntries = [
  { href: props.alter ? `/alters/${props.alter.id}/edit` : '/alters/create', icon: 'people', label: 'Profil', hint: 'Nom, handle, bio, avatar, couleur, grade de confidentialité' },
  { href: '/blocks', icon: 'shield', label: 'Blocages', hint: 'Alters et comptes masqués pour cet alter' },
  { href: '/follows', icon: 'people', label: 'Relations', hint: 'Abonnés et abonnements de cet alter' },
]

const systemEntries = [
  { href: '/alters', icon: 'people', label: 'Mes alters', hint: 'Créer, éditer, supprimer, restaurer' },
  { href: '/settings/messaging', icon: 'message', label: 'Messagerie', hint: 'Mode perso ou partagé, surface publique, seuil de switch' },
  { href: '/settings/security', icon: 'key', label: 'Sécurité', hint: 'Adresse, double authentification, codes de secours' },
]
</script>

<template>
  <Head title="Paramètres" />
  <AppLayout>
    <div class="flex flex-col gap-6">
      <h1 class="font-display text-3xl">Paramètres</h1>

      <div class="flex gap-1 border-b border-line-soft">
        <button
          v-for="entry in [{ key: 'alter', label: alter?.name ?? 'Alter' }, { key: 'systeme', label: 'Système' }]"
          :key="entry.key"
          type="button"
          class="-mb-px border-b-2 px-4 py-2.5 text-sm transition"
          :class="tab === entry.key
            ? 'border-accent font-semibold text-ink'
            : 'border-transparent text-faint hover:text-muted'"
          @click="tab = entry.key"
        >
          {{ entry.label }}
        </button>
      </div>

      <template v-if="tab === 'alter'">
        <div v-if="alter" class="flex items-center gap-3">
          <Avatar :alter="alter" :size="48" />
          <div class="min-w-0">
            <p class="truncate font-semibold">{{ alter.name }}</p>
            <p class="truncate text-sm text-faint">@{{ alter.handle }}</p>
          </div>
        </div>

        <div class="flex flex-col gap-2">
          <Link
            v-for="entry in alterEntries"
            :key="entry.href"
            :href="entry.href"
            class="za-card flex items-center gap-4 p-4 transition hover:border-accent/40"
          >
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/6 text-accent">
              <Icon :name="entry.icon" :size="19" />
            </span>
            <span class="min-w-0">
              <span class="block font-semibold">{{ entry.label }}</span>
              <span class="block text-sm text-faint">{{ entry.hint }}</span>
            </span>
            <span class="ml-auto text-faint" aria-hidden="true">›</span>
          </Link>
        </div>
      </template>

      <template v-else>
        <div class="za-card flex flex-wrap items-center gap-3 p-4 text-sm">
          <span class="text-muted">{{ email }}</span>
          <span
            class="rounded-full px-2.5 py-0.5 text-xs"
            :class="verified ? 'bg-mint/15 text-mint' : 'bg-accent/15 text-accent'"
          >
            {{ verified ? 'adresse vérifiée' : 'adresse non vérifiée' }}
          </span>
          <span class="ml-auto text-xs text-faint">{{ alters.data.length }} alters</span>
        </div>

        <div class="flex flex-col gap-2">
          <Link
            v-for="entry in systemEntries"
            :key="entry.href"
            :href="entry.href"
            class="za-card flex items-center gap-4 p-4 transition hover:border-accent/40"
          >
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/6 text-accent">
              <Icon :name="entry.icon" :size="19" />
            </span>
            <span class="min-w-0">
              <span class="block font-semibold">{{ entry.label }}</span>
              <span class="block text-sm text-faint">{{ entry.hint }}</span>
            </span>
            <span class="ml-auto text-faint" aria-hidden="true">›</span>
          </Link>
        </div>
      </template>
    </div>
  </AppLayout>
</template>
