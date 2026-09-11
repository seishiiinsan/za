<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'

const useRecovery = ref(false)
const form = useForm({ code: '', recovery_code: '' })
</script>

<template>
  <Head title="Second facteur" />
  <AppLayout>
    <form class="mx-auto max-w-sm space-y-4" @submit.prevent="form.post('/two-factor-challenge')">
      <h1 class="text-xl font-semibold">Second facteur</h1>

      <label v-if="!useRecovery" class="block text-sm">
        Code de votre application
        <input
          v-model="form.code"
          inputmode="numeric"
          autocomplete="one-time-code"
          class="mt-1 w-full rounded-md border border-neutral-800 bg-neutral-900 px-3 py-2 text-sm"
        />
      </label>

      <label v-else class="block text-sm">
        Code de secours
        <input v-model="form.recovery_code" class="mt-1 w-full rounded-md border border-neutral-800 bg-neutral-900 px-3 py-2 text-sm" />
      </label>

      <p v-if="form.errors.code" class="text-xs text-red-400">{{ form.errors.code }}</p>

      <button :disabled="form.processing" class="w-full rounded-md bg-violet-600 px-4 py-2 text-sm font-medium hover:bg-violet-500 disabled:opacity-50">
        Valider
      </button>

      <button type="button" class="block w-full text-center text-sm text-neutral-500 hover:text-neutral-300" @click="useRecovery = !useRecovery">
        {{ useRecovery ? "Utiliser l'application" : 'Utiliser un code de secours' }}
      </button>
    </form>
  </AppLayout>
</template>
