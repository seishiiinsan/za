<script setup>
import { computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AlterRail from '../Components/AlterRail.vue'
import Avatar from '../Components/Avatar.vue'
import Icon from '../Components/Icon.vue'

defineProps({
  // Colonne de droite optionnelle, façon Instagram : contexte, jamais de suggestions de comptes.
  wide: { type: Boolean, default: false },
})

const page = usePage()
const auth = computed(() => page.props.auth)
const flash = computed(() => page.props.flash ?? {})
const active = computed(() => auth.value.activeAlter)
const url = computed(() => page.url)

const links = [
  { href: '/feed', label: 'Feed', icon: 'feed' },
  { href: '/search', label: 'Recherche', icon: 'search' },
  { href: '/notifications', label: 'Notifications', icon: 'bell' },
  { href: '/conversations', label: 'Messages', icon: 'message' },
  { href: '/follows', label: 'Abonnements', icon: 'people' },
  { href: '/posts/invitations', label: 'Invitations', icon: 'invite' },
  { href: '/dashboard', label: 'Chez toi', icon: 'home' },
]

// Les écrans de réglage restent accessibles, sans encombrer la barre principale.
const secondary = [
  { href: '/alters', label: 'Mes alters' },
  { href: '/blocks', label: 'Blocages' },
  { href: '/settings/messaging', label: 'Messagerie' },
  { href: '/settings/security', label: 'Sécurité' },
]

// Cinq entrées au maximum en bas d'écran : au-delà, les libellés se chevauchent.
// Invitations et abonnements restent atteignables depuis « Chez toi » et les profils.
const mobileHidden = ['/posts/invitations', '/follows']
const mobileLinks = computed(() => links.filter((link) => !mobileHidden.includes(link.href)))

function isCurrent(href) {
  return url.value === href || url.value.startsWith(`${href}/`)
}

function logout() {
  router.post('/logout')
}
</script>

<template>
  <div class="relative z-10 min-h-screen">
    <!-- Visiteur non connecté : en-tête simple, pas de coquille d'application. -->
    <template v-if="!auth.authenticated">
      <header class="border-b border-line-soft">
        <div class="mx-auto flex max-w-3xl items-center gap-4 px-5 py-4">
          <Link href="/" class="font-display text-2xl text-accent">座</Link>
          <div class="ml-auto flex items-center gap-3">
            <Link href="/login" class="za-btn-ghost">Connexion</Link>
            <Link href="/register" class="za-btn">Créer un système</Link>
          </div>
        </div>
      </header>

      <main class="mx-auto w-full max-w-3xl px-5 py-8">
        <p v-if="flash.status" class="za-card mb-5 px-4 py-3 text-sm text-accent-soft">{{ flash.status }}</p>
        <p v-if="flash.error" class="za-card mb-5 px-4 py-3 text-sm text-danger">{{ flash.error }}</p>
        <slot />
      </main>
    </template>

    <!-- Système connecté : rail d'alters, barre de navigation, colonne centrale. -->
    <div v-else class="flex flex-col lg:flex-row">
      <div class="sticky top-0 z-30 bg-ground/85 backdrop-blur lg:static lg:bg-transparent lg:backdrop-blur-none">
        <AlterRail />
      </div>

      <aside class="hidden w-[236px] shrink-0 flex-col gap-1 border-r border-line-soft px-4 py-6 lg:flex lg:h-screen lg:sticky lg:top-0">
        <Link
          v-for="link in links"
          :key="link.href"
          :href="link.href"
          class="flex items-center gap-3 rounded-2xl px-3 py-2.5 text-[0.95rem] transition"
          :class="isCurrent(link.href) ? 'bg-white/8 font-semibold text-ink' : 'text-muted hover:bg-white/5 hover:text-ink'"
        >
          <Icon :name="link.icon" />
          {{ link.label }}
        </Link>

        <div class="my-3 h-px bg-line-soft" />

        <Link
          v-for="link in secondary"
          :key="link.href"
          :href="link.href"
          class="rounded-2xl px-3 py-2 text-sm transition"
          :class="isCurrent(link.href) ? 'text-ink' : 'text-faint hover:text-muted'"
        >
          {{ link.label }}
        </Link>

        <div class="mt-auto flex flex-col gap-3">
          <Link
            v-if="active"
            :href="`/@${active.handle}`"
            class="flex items-center gap-3 rounded-2xl px-3 py-2 transition hover:bg-white/5"
          >
            <Avatar :alter="active" :size="34" />
            <span class="min-w-0">
              <span class="block truncate text-sm font-semibold">{{ active.name }}</span>
              <span class="block truncate text-xs text-faint">au front</span>
            </span>
          </Link>
          <button class="flex items-center gap-3 px-3 py-2 text-sm text-faint transition hover:text-muted" @click="logout">
            <Icon name="logout" :size="18" />
            Quitter
          </button>
        </div>
      </aside>

      <div class="flex min-w-0 flex-1 justify-center gap-8 px-4 pb-24 pt-6 lg:px-10 lg:pb-10">
        <main class="w-full min-w-0" :class="wide ? 'max-w-4xl' : 'max-w-[640px]'">
          <p v-if="flash.status" class="za-card mb-5 px-4 py-3 text-sm text-accent-soft">{{ flash.status }}</p>
          <p v-if="flash.error" class="za-card mb-5 px-4 py-3 text-sm text-danger">{{ flash.error }}</p>
          <slot />
        </main>

        <aside v-if="$slots.aside" class="hidden w-[320px] shrink-0 xl:block">
          <slot name="aside" />
        </aside>
      </div>

      <!-- Mobile : barre basse, comme sur l'application Instagram. -->
      <nav class="fixed inset-x-0 bottom-0 z-30 flex justify-around border-t border-line-soft bg-ground/95 px-2 py-2 backdrop-blur lg:hidden">
        <Link
          v-for="link in mobileLinks"
          :key="link.href"
          :href="link.href"
          class="flex flex-col items-center gap-1 rounded-xl px-3 py-1.5 text-[0.65rem]"
          :class="isCurrent(link.href) ? 'text-accent' : 'text-faint'"
          :title="link.label"
        >
          <Icon :name="link.icon" :size="21" />
          {{ link.label }}
        </Link>
      </nav>
    </div>
  </div>
</template>
