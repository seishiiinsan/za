<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import AuthCard from '../../Components/AuthCard.vue'

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
    <AuthCard title="Nouveau mot de passe">
      <form class="flex flex-col gap-4" @submit.prevent="form.post('/reset-password')">
        <label class="block">
          <span class="za-label">E-mail</span>
          <input v-model="form.email" type="email" required class="za-input" />
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
        <button type="submit" class="za-btn w-full" :disabled="form.processing">Valider</button>
      </form>
    </AuthCard>
  </AppLayout>
</template>
