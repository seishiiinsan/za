<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import AuthCard from '../../Components/AuthCard.vue'

const form = useForm({ email: '', password: '', password_confirmation: '' })
</script>

<template>
  <Head title="Créer un système" />
  <AppLayout>
    <AuthCard
      title="Créer un système"
      intro="Le système reste privé : il n'a pas de profil, n'est pas cherchable, et son identifiant ne sort jamais."
    >
      <form class="flex flex-col gap-4" @submit.prevent="form.post('/register')">
        <label class="block">
          <span class="za-label">E-mail</span>
          <input v-model="form.email" type="email" required autocomplete="email" class="za-input" />
          <span v-if="form.errors.email" class="za-error">{{ form.errors.email }}</span>
        </label>

        <label class="block">
          <span class="za-label">Mot de passe</span>
          <input v-model="form.password" type="password" required autocomplete="new-password" class="za-input" />
          <span v-if="form.errors.password" class="za-error">{{ form.errors.password }}</span>
        </label>

        <label class="block">
          <span class="za-label">Confirmation</span>
          <input v-model="form.password_confirmation" type="password" required autocomplete="new-password" class="za-input" />
        </label>

        <button type="submit" class="za-btn w-full" :disabled="form.processing">Créer</button>
      </form>

      <template #footer>
        <Link href="/login" class="hover:text-muted">J'ai déjà un système</Link>
      </template>
    </AuthCard>
  </AppLayout>
</template>
