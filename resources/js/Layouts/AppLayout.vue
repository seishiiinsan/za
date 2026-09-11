<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import Avatar from '../Components/Avatar.vue'
import Icon from '../Components/Icon.vue'
import NotificationBell from '../Components/NotificationBell.vue'
import ProfileMenu from '../Components/ProfileMenu.vue'
import Toasts from '../Components/Toasts.vue'

const page = usePage()
const auth = computed(() => page.props.auth)
const active = computed(() => auth.value.activeAlter)
const url = computed(() => page.url)

// Une seule barre : quatre destinations. Le reste vit dans la cloche et le menu profil.
const links = [
  { href: '/feed', label: 'Feed', icon: 'feed' },
  { href: '/search', label: 'Recherche', icon: 'search' },
  { href: '/conversations', label: 'Messages', icon: 'message' },
  { href: '/dashboard', label: 'Chez toi', icon: 'home' },
]

function isCurrent(href) {
  return url.value === href || url.value.startsWith(`${href}/`)
}
</script>

<template>
  <div class="relative z-10 min-h-screen">
    <Toasts />

    <!-- Visiteur non connecté : en-tête simple, pas de coquille d'application. -->
    <template v-if="!auth.authenticated">
      <header class="border-b border-line-soft">
        <div class="mx-auto flex max-w-5xl items-center gap-4 px-5 py-4">
          <Link href="/" class="font-display text-2xl text-accent">座</Link>
          <div class="ml-auto flex items-center gap-3">
            <Link href="/login" class="za-btn-ghost">Connexion</Link>
            <Link href="/register" class="za-btn">Créer un système</Link>
          </div>
        </div>
      </header>

      <main class="mx-auto w-full max-w-5xl px-5 py-8">
        <slot />
      </main>
    </template>

    <!-- Système connecté. -->
    <div v-else class="flex flex-col lg:flex-row">
      <aside
        class="hidden w-[248px] shrink-0 flex-col gap-1 border-r border-line-soft px-4 py-5 lg:sticky lg:top-0 lg:flex lg:h-screen"
      >
        <div class="mb-3 flex items-center gap-2 px-2">
          <Link href="/feed" class="font-display text-3xl text-accent" title="Za">座</Link>
          <div class="ml-auto">
            <NotificationBell />
          </div>
        </div>

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

        <div class="mt-auto">
          <ProfileMenu />
        </div>
      </aside>

      <!-- Mobile : une barre en haut pour l'identité et la cloche… -->
      <header
        class="sticky top-0 z-30 flex items-center gap-3 border-b border-line-soft bg-ground/90 px-4 py-2.5 backdrop-blur lg:hidden"
      >
        <Link href="/feed" class="font-display text-2xl text-accent">座</Link>
        <Link v-if="active" href="/dashboard" class="ml-auto flex items-center gap-2 text-sm font-semibold">
          <Avatar :alter="active" :size="28" />
          {{ active.name }}
        </Link>
        <NotificationBell />
      </header>

      <div class="flex min-w-0 flex-1 justify-center px-4 pb-24 pt-5 lg:px-10 lg:pb-10 lg:pt-8">
        <main class="w-full min-w-0 max-w-[640px]">
          <slot />
        </main>
      </div>

      <!-- …et une barre en bas pour les quatre destinations. -->
      <nav
        class="fixed inset-x-0 bottom-0 z-30 flex justify-around border-t border-line-soft bg-ground/95 px-2 py-2 backdrop-blur lg:hidden"
      >
        <Link
          v-for="link in links"
          :key="link.href"
          :href="link.href"
          class="flex flex-col items-center gap-1 rounded-xl px-4 py-1.5 text-[0.65rem]"
          :class="isCurrent(link.href) ? 'text-accent' : 'text-faint'"
        >
          <Icon :name="link.icon" :size="22" />
          {{ link.label }}
        </Link>
        <Link
          href="/settings"
          class="flex flex-col items-center gap-1 rounded-xl px-4 py-1.5 text-[0.65rem]"
          :class="isCurrent('/settings') ? 'text-accent' : 'text-faint'"
        >
          <Icon name="shield" :size="22" />
          Réglages
        </Link>
      </nav>
    </div>
  </div>
</template>
