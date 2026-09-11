<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'

defineProps({ email: { type: String, required: true } })

const form = useForm({})
</script>

<template>
  <Head title="Vérifier l'adresse" />
  <AppLayout>
    <div class="mx-auto max-w-sm space-y-4">
      <h1 class="text-xl font-semibold">Vérifiez votre adresse</h1>
      <p class="text-sm text-neutral-400">
        Un lien est parti à {{ email }}. Il ouvre l'accès au reste du compte. Vous pouvez déjà
        régler la sécurité de votre compte.
      </p>
      <button
        :disabled="form.processing"
        class="w-full rounded-md bg-violet-600 px-4 py-2 text-sm font-medium hover:bg-violet-500 disabled:opacity-50"
        @click="form.post('/verify-email/send')"
      >
        Renvoyer le lien
      </button>
    </div>
  </AppLayout>
</template>
