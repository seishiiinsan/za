<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'

const form = useForm({ email: '' })
</script>

<template>
  <Head title="Mot de passe oublié" />
  <AppLayout>
    <form class="mx-auto max-w-sm space-y-4" @submit.prevent="form.post('/forgot-password')">
      <h1 class="text-xl font-semibold">Réinitialiser</h1>
      <label class="block text-sm">
        E-mail
        <input v-model="form.email" type="email" required class="mt-1 w-full rounded-md border border-neutral-800 bg-neutral-900 px-3 py-2 text-sm" />
        <span v-if="form.errors.email" class="text-xs text-red-400">{{ form.errors.email }}</span>
      </label>
      <button :disabled="form.processing" class="w-full rounded-md bg-violet-600 px-4 py-2 text-sm font-medium hover:bg-violet-500 disabled:opacity-50">
        Envoyer le lien
      </button>
    </form>
  </AppLayout>
</template>
