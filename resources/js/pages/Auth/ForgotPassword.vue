<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import AuthCard from '../../Components/AuthCard.vue'

const form = useForm({ email: '' })
</script>

<template>
  <Head title="Mot de passe oublié" />
  <AppLayout>
    <AuthCard title="Réinitialiser" intro="Un lien part à cette adresse, si un système lui est rattaché.">
      <form class="flex flex-col gap-4" @submit.prevent="form.post('/forgot-password')">
        <label class="block">
          <span class="za-label">E-mail</span>
          <input v-model="form.email" type="email" required class="za-input" />
          <span v-if="form.errors.email" class="za-error">{{ form.errors.email }}</span>
        </label>
        <button type="submit" class="za-btn w-full" :disabled="form.processing">Envoyer le lien</button>
      </form>
    </AuthCard>
  </AppLayout>
</template>
