<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import AuthCard from '../../Components/AuthCard.vue'

defineProps({ email: { type: String, required: true } })

const form = useForm({})
</script>

<template>
  <Head title="Vérifier l'adresse" />
  <AppLayout>
    <AuthCard title="Vérifie ton adresse">
      <div class="flex flex-col gap-4">
        <p class="text-sm leading-relaxed text-muted">
          Un lien est parti à <span class="text-ink">{{ email }}</span>. Il ouvre l'accès au reste
          du compte. Tu peux déjà régler la sécurité en attendant.
        </p>
        <button class="za-btn w-full" :disabled="form.processing" @click="form.post('/verify-email/send')">
          Renvoyer le lien
        </button>
        <Link href="/settings/security" class="za-btn-ghost w-full">Régler la sécurité</Link>
      </div>
    </AuthCard>
  </AppLayout>
</template>
