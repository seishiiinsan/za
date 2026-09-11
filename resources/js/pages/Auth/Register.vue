<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'

const form = useForm({ email: '', password: '', password_confirmation: '' })
</script>

<template>
  <Head title="Créer un système" />
  <AppLayout>
    <form class="mx-auto max-w-sm space-y-4" @submit.prevent="form.post('/register')">
      <h1 class="text-xl font-semibold">Créer un système</h1>
      <p class="text-sm text-neutral-500">Le système est privé : il ne sera jamais visible publiquement.</p>

      <label class="block text-sm">
        E-mail
        <input v-model="form.email" type="email" required class="mt-1 w-full rounded-md border border-neutral-800 bg-neutral-900 px-3 py-2 text-sm" />
        <span v-if="form.errors.email" class="text-xs text-red-400">{{ form.errors.email }}</span>
      </label>

      <label class="block text-sm">
        Mot de passe
        <input v-model="form.password" type="password" required class="mt-1 w-full rounded-md border border-neutral-800 bg-neutral-900 px-3 py-2 text-sm" />
        <span v-if="form.errors.password" class="text-xs text-red-400">{{ form.errors.password }}</span>
      </label>

      <label class="block text-sm">
        Confirmation
        <input v-model="form.password_confirmation" type="password" required class="mt-1 w-full rounded-md border border-neutral-800 bg-neutral-900 px-3 py-2 text-sm" />
      </label>

      <button :disabled="form.processing" class="w-full rounded-md bg-violet-600 px-4 py-2 text-sm font-medium hover:bg-violet-500 disabled:opacity-50">
        Créer
      </button>
      <Link href="/login" class="block text-center text-sm text-neutral-500 hover:text-neutral-300">J'ai déjà un système</Link>
    </form>
  </AppLayout>
</template>
