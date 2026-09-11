<script setup>
import { computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AlterSwitcher from '../Components/AlterSwitcher.vue'

const page = usePage()
const auth = computed(() => page.props.auth)
const flash = computed(() => page.props.flash ?? {})

const links = [
  { href: '/dashboard', label: 'Dashboard' },
  { href: '/feed', label: 'Feed' },
  { href: '/alters', label: 'Alters' },
  { href: '/follows', label: 'Abonnements' },
  { href: '/search', label: 'Recherche' },
]

function logout() {
  router.post('/logout')
}
</script>

<template>
  <div class="min-h-screen">
    <header class="border-b border-neutral-900">
      <div class="mx-auto flex max-w-3xl flex-wrap items-center gap-4 px-4 py-3">
        <Link href="/" class="text-lg font-semibold tracking-tight">座<span class="sr-only">Za</span></Link>
        <nav v-if="auth.authenticated" class="flex flex-wrap gap-3 text-sm text-neutral-400">
          <Link v-for="link in links" :key="link.href" :href="link.href" class="hover:text-neutral-100">
            {{ link.label }}
          </Link>
        </nav>
        <div class="ml-auto flex items-center gap-3">
          <AlterSwitcher v-if="auth.authenticated" />
          <button v-if="auth.authenticated" class="text-sm text-neutral-500 hover:text-neutral-200" @click="logout">
            Quitter
          </button>
          <template v-else>
            <Link href="/login" class="text-sm text-neutral-400 hover:text-neutral-100">Connexion</Link>
            <Link href="/register" class="text-sm text-violet-400 hover:text-violet-300">Créer un système</Link>
          </template>
        </div>
      </div>
    </header>

    <main class="mx-auto max-w-3xl space-y-4 px-4 py-6">
      <p v-if="flash.status" class="rounded-md border border-violet-900 bg-violet-950/40 px-3 py-2 text-sm text-violet-200">
        {{ flash.status }}
      </p>
      <p v-if="flash.error" class="rounded-md border border-red-900 bg-red-950/40 px-3 py-2 text-sm text-red-200">
        {{ flash.error }}
      </p>
      <slot />
    </main>
  </div>
</template>
