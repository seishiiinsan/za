<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import AuthCard from '../../Components/AuthCard.vue'

const form = useForm({ email: '', password: '', remember: false })
</script>

<template>
  <Head title="Connexion" />
  <AppLayout>
    <AuthCard title="Connexion" intro="Le compte système est la seule porte d'entrée. Tes alters vivent derrière.">
      <form class="flex flex-col gap-4" @submit.prevent="form.post('/login')">
        <label class="block">
          <span class="za-label">E-mail</span>
          <input v-model="form.email" type="email" required autocomplete="email" class="za-input" />
          <span v-if="form.errors.email" class="za-error">{{ form.errors.email }}</span>
        </label>

        <label class="block">
          <span class="za-label">Mot de passe</span>
          <input v-model="form.password" type="password" required autocomplete="current-password" class="za-input" />
        </label>

        <label class="flex items-center gap-2 text-sm text-muted">
          <input v-model="form.remember" type="checkbox" class="accent-[#ff9f78]" />
          Rester connecté
        </label>

        <button type="submit" class="za-btn w-full" :disabled="form.processing">Entrer</button>
      </form>

      <template #footer>
        <Link href="/forgot-password" class="hover:text-muted">Mot de passe oublié</Link>
      </template>
    </AuthCard>
  </AppLayout>
</template>
