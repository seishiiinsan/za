<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'

const props = defineProps({ token: String, email: String })

const form = useForm({
  token: props.token,
  email: props.email ?? '',
  password: '',
  password_confirmation: '',
})
</script>

<template>
  <Head title="Nouveau mot de passe" />
  <AppLayout>
    <form class="mx-auto max-w-sm space-y-4" @submit.prevent="form.post('/reset-password')">
      <h1 class="text-xl font-semibold">Nouveau mot de passe</h1>
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
        Valider
      </button>
    </form>
  </AppLayout>
</template>
