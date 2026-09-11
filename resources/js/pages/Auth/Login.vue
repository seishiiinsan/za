<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'

const form = useForm({ email: '', password: '', remember: false })
</script>

<template>
  <Head title="Connexion" />
  <AppLayout>
    <form class="mx-auto max-w-sm space-y-4" @submit.prevent="form.post('/login')">
      <h1 class="text-xl font-semibold">Connexion système</h1>

      <label class="block text-sm">
        E-mail
        <input v-model="form.email" type="email" required class="mt-1 w-full rounded-md border border-neutral-800 bg-neutral-900 px-3 py-2 text-sm" />
        <span v-if="form.errors.email" class="text-xs text-red-400">{{ form.errors.email }}</span>
      </label>

      <label class="block text-sm">
        Mot de passe
        <input v-model="form.password" type="password" required class="mt-1 w-full rounded-md border border-neutral-800 bg-neutral-900 px-3 py-2 text-sm" />
      </label>

      <label class="flex items-center gap-2 text-sm text-neutral-400">
        <input v-model="form.remember" type="checkbox" /> Rester connecté
      </label>

      <button :disabled="form.processing" class="w-full rounded-md bg-violet-600 px-4 py-2 text-sm font-medium hover:bg-violet-500 disabled:opacity-50">
        Entrer
      </button>
      <Link href="/forgot-password" class="block text-center text-sm text-neutral-500 hover:text-neutral-300">Mot de passe oublié</Link>
    </form>
  </AppLayout>
</template>
