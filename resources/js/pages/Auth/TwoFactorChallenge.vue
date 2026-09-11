<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import AuthCard from '../../Components/AuthCard.vue'

const useRecovery = ref(false)
const form = useForm({ code: '', recovery_code: '' })
</script>

<template>
  <Head title="Second facteur" />
  <AppLayout>
    <AuthCard title="Second facteur" intro="Le mot de passe seul n'ouvre pas la session.">
      <form class="flex flex-col gap-4" @submit.prevent="form.post('/two-factor-challenge')">
        <label v-if="!useRecovery" class="block">
          <span class="za-label">Code de ton application</span>
          <input
            v-model="form.code"
            inputmode="numeric"
            autocomplete="one-time-code"
            placeholder="123456"
            class="za-input text-center text-xl tracking-[0.4em]"
          />
        </label>

        <label v-else class="block">
          <span class="za-label">Code de secours</span>
          <input v-model="form.recovery_code" class="za-input text-center" />
        </label>

        <p v-if="form.errors.code" class="za-error">{{ form.errors.code }}</p>

        <button type="submit" class="za-btn w-full" :disabled="form.processing">Valider</button>
        <button type="button" class="text-sm text-faint hover:text-muted" @click="useRecovery = !useRecovery">
          {{ useRecovery ? "Utiliser l'application" : 'Utiliser un code de secours' }}
        </button>
      </form>
    </AuthCard>
  </AppLayout>
</template>
